<script>
  const {
    createApp,
    ref,
    onMounted,
    computed
  } = Vue;

  const app = createApp({
    setup() {
      const program = <?= json_encode($program ?? "") ?>;
      const listTeamMS = ref([])
      const tableData = ref([])
      const createdProgram = ref([])
      const selectedManager = ref(null);
      const searchManager = ref('');
      const searchResults = ref([]);
      const manager = ref(null);
      const ruleFormRef = ref(null);
      const listProposer = ref([]);
      const paramUserId = ref(null);
      const operation = ref(true);
      const form = ref({
        proposer: '',
        manager: '',
        team_ms: '',
        kpi: ''
      });

      const debounce = (fn, delay) => {
        let timeout;

        return (...args) => {
          if (timeout) clearTimeout(timeout);

          timeout = setTimeout(() => {
            fn.apply(this, args);
          }, delay);
        };
      };

      const removeManager = () => {
        selectedManager.value = null;
        form.value.manager = '';
        ruleFormRef.value.clearValidate('manager');
        listTeamMS.value = [];
        tableData.value = [];
        form.value.team_ms = '';
        checkEnabelSelect();
      };

      const selectManager = async (manager) => {
        selectedManager.value = {
          id: manager.id,
          name: manager.name,
          avatar: manager.avatar || '',
          department: manager.department,
          position: manager.position || '',
          work_position: manager.work_position || '',
          type: manager.type || ""
        };
        await getListTeamMS(manager.id);
        checkEnabelSelect();
        form.value.manager = manager.id;
        searchResults.value = [];
        searchManager.value = '';
        ruleFormRef.value.clearValidate('manager');

      };

      const debouncedSearch = debounce((query) => {
        handleSearchManager(query);
      }, 500);

      async function getListTeamMS(userid = null) {
        try {
          const params = userid ? {
            user_id: userid
          } : {};
          const response = await axios.get(`../../api/get_list_team_ms.php`, {
            params
          });
          const data = response.data;
          if (data.success && data.data) {
            listTeamMS.value = Object.entries(data.data).map(([id, name]) => ({
              ID: id,
              NAME: name
            }));
          } else {
            console.error('API Error:', data.message);
            listTeamMS.value = {};
          }
        } catch (error) {
          console.error('Error fetching listTeamMS:', error);
          listTeamMS.value = {};
        }
      }

      const handleSearchManager = async (name = '', userid = null) => {
        if (!name && !userid) {
          searchResults.value = [];
          return;
        }
        const params = userid ? {
          user_id: userid
        } : name ? {
          name: name
        } : {};
        try {
          const response = await axios.get(`../../api/search_manager.php`, {
            params
          });

          if (response.data.success && response.data.data) {
            searchResults.value = response.data.data.map(user => ({
              id: user.ID,
              name: `${user.LAST_NAME} ${user.NAME}`.trim(),
              department: user.UF_DEPARTMENT?.[0] || '',
              position: user.POSITION || '',
              work_position: user.WORK_POSITION || '',
              type: user.USER_TYPE,
              avatar: user.PERSONAL_PHOTO || ''
            }));
          } else {
            console.error('API Error:', response.data.message);
            searchResults.value = [];
          }
        } catch (error) {
          console.error('Error searching for manager:', error);
          searchResults.value = [];
        }
      };

      const debouncedHandleInputChange = debounce((index, month) => {
        handleInputChange(index, month);
      }, 500);

      const getListProposalUnit = async () => {
        try {
          const response = await axios.get(`../../api/get_list_proposal_unit.php`);
          const data = response.data;
          if (data.success && data.data) {
            data.data.forEach(element => {
              listProposer.value.push({
                id: element.id,
                name: element.value
              });
            });
          } else {
            console.error('API Error:', data.message);
            listProposer.value = [];
          }
        } catch (error) {
          console.error('Error fetching listProposalUnit:', error);
          listProposer.value = [];
        }
      }

      const handleInputChange = (index, month, value) => {
        tableData.value[index][`m${month}`] = value;
      }

      const deleteRow = (index, program) => {
        tableData.value.splice(index, 1)
        createdProgram.value.splice(createdProgram.value.indexOf(program), 1)
      }

      const onAddItem = (program) => {
        if (!createdProgram.value.includes(program)) {
          createdProgram.value.push(program)
          const newRow = {
            program: program,
          };
          for (let i = 1; i <= 12; i++) {
            newRow[`m${i}`] = '';
          }
          tableData.value.push(newRow);
        } else {
          ElementPlus.ElNotification({
            message: 'Chương trình này đã được tạo!',
            type: 'warning',
            duration: 2000,
            position: 'top-right'
          });
        }

      }

      const getUserKpi = async (proposer, manager, team_ms) => {
        try {
          const response = await axios.get(`../../api/get_user_kpi.php`, {
            params: {
              proposer: proposer,
              manager: manager,
              team_ms: team_ms
            }
          });
          const data = response.data;
          if (data.success && data.data) {
            data.data.forEach(element => {
              console.log(element);
              JSON.parse(element.kpi).forEach(element => {
                if (element.program) {
                  const newRow = {
                    program: element.program,
                  };
                  for (let i = 1; i <= 12; i++) {
                    newRow[`m${i}`] = element[`m${i}`];
                  }
                  tableData.value.push(newRow);
                }
              });

            });
          } else {
            console.error('API Error:', data.message);
            tableData.value = [];
          }
        } catch (error) {
          console.error('Error fetching listProposalUnit:', error);
          tableData.value = [];
        }
      }

      onMounted(async () => {
        checkEnabelSelect();
        await getListProposalUnit();
        const urlParams = new URLSearchParams(window.location.search);
        form.value.proposer = urlParams.get('proposer_id');
        form.value.manager = urlParams.get('user_id');
        form.value.team_ms = urlParams.get('team_ms');
        if (form.value.manager) {
          await handleSearchManager(null, form.value.manager);
          if (searchResults.value.length > 0) {
            selectManager(searchResults.value[0]);
          }
        }
        if (form.value.proposer && form.value.manager && form.value.team_ms) {
          await getUserKpi(form.value.proposer, form.value.manager, form.value.team_ms);
          operation.value = false;
        }
      });

      const submitForm = async () => {
        form.value.kpi = tableData.value.map(element => ({
          ...element
        }));

        const response = await axios.post(`../../api/create_kpi.php`, form.value);
        // ruleFormRef.value.validate(async (valid, fields) => {
        //     if (valid) {

        //     }
        // })
      };

      const getTableData = async () => {
        tableData.value = [];
        if (form.value.proposer && form.value.manager && form.value.team_ms) {
          await getUserKpi(form.value.proposer, form.value.manager, form.value.team_ms);
          operation.value = false;
        }
      }

      const checkEnabelSelect = () => {
        return form.value.proposer && form.value.manager && form.value.team_ms
      }
      return {
        tableData,
        onAddItem,
        deleteRow,
        program,
        listProposer,
        form,
        listTeamMS,
        selectedManager,
        searchManager,
        searchResults,
        removeManager,
        debouncedSearch,
        selectManager,
        ruleFormRef,
        submitForm,
        handleInputChange,
        debouncedHandleInputChange,
        getTableData,
        checkEnabelSelect
      }
    }
  });

  app.use(ElementPlus);
  app.mount('#form-kpi');
</script>