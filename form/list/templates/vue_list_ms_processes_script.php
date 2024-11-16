<script>
  const {
    createApp,
    ref,
    onMounted,
    computed
  } = Vue;

  const app = createApp({
    setup() {
      const tableData = ref([]);
      const tableDataKpi = ref([])
      const showFormKPI = ref(false);
      const createdProgram = ref([]);
      const selectedProgram = ref('');
      const listProposer = ref([]);
      const allData = ref([]);
      const flag = ref(false);
      const currMonth = ref(new Date().getMonth() + 1);
      const userId = <?= json_encode($userID ?? "") ?>;
      let listProgram = <?= json_encode($program ?? "") ?>;
      let urlUserInfo = <?= json_encode($config["url_user_info"] ?? "") ?>;
      let urlTeamMSInfo = <?= json_encode($config["url_team_ms_info"] ?? "") ?>;
      const isEdit = ref(false);
      const loading = ref(false);
      const rejectLoading = ref(false);
      const approveLoading = ref(false);
      const currentPage = ref(1);
      const pageSize = ref(50);
      const total = ref(0);
      const searchQuery = ref('');
      const form = ref({});

      const handleSearch = () => {};

      const handleApprove = async (row) => {
        try {
          approveLoading.value = true;
          const params = {
            id: row.id,
            stage_id: row.stage_id,
            user_name: row.user_name,
            user_email: row.user_email,
            employee_id: row.employee_id,
            department: row.department,
            type_ms: row.type_ms,
            team_ms: row.team_ms,
            propose: row.propose,
          };

          const response = await axios.post('../../api/approve_ms_register.php', params);

          if (response.data.success) {
            ElementPlus.ElMessage({
              message: 'Xét duyệt thành công',
              type: 'success'
            });
            window.location.reload();
          } else {
            if (response.data.code === 'STAGE_MISMATCH') {
              ElementPlus.ElMessage({
                message: 'Yêu cầu này đã được xử lý bởi người khác. Trang sẽ được tải lại.',
                type: 'warning',
                duration: 2000
              });
              setTimeout(() => {
                window.location.reload();
              }, 2000);
            } else {
              ElementPlus.ElMessage({
                message: response.data.error || 'Có lỗi xảy ra',
                type: 'error',
                duration: 3000
              });
            }
          }
        } catch (error) {
          let errorMessage = 'Có lỗi xảy ra khi xét duyệt';

          if (error.response) {
            errorMessage = error.response.data?.error || errorMessage;
          } else if (error.request) {
            errorMessage = 'Không thể kết nối đến server';
          } else {
            errorMessage = error.message;
          }

          ElementPlus.ElMessage({
            message: errorMessage,
            type: 'error',
            duration: 3000
          });
        } finally {
          approveLoading.value = false;
        }
      };

      const handleReject = async (row) => {
        try {
          ElementPlus.ElMessageBox.prompt('Vui lòng nhập lý do từ chối', 'Từ chối', {
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            inputValidator: (value) => {
              if (!value) {
                return 'Vui lòng nhập lý do từ chối';
              }
            }
          }).then(async ({
            value
          }) => {
            rejectLoading.value = true;
            try {
              const response = await axios.post('../../api/reject_ms_register.php', {
                id: row.id,
                stage_id: row.stage_id,
                user_name: row.user_name,
                user_email: row.user_email,
                employee_id: row.employee_id,
                department: row.department,
                type_ms: row.type_ms,
                team_ms: row.team_ms,
                comments: value,
                reviewer: row.reviewers.find(reviewer => reviewer.stage_id.toString() === row.stage_id)?.stage_label
              });
              if (response.data.success) {
                ElementPlus.ElMessage({
                  message: 'Từ chối thành công',
                  type: 'success'
                });
                window.location.reload();
              } else {
                rejectLoading.value = false;
                if (response.data.code === 'STAGE_MISMATCH') {
                  ElementPlus.ElMessage({
                    message: 'Yêu cầu này đã được xử lý bởi người khác. Trang sẽ được tải lại.',
                    type: 'warning',
                    duration: 2000
                  });
                  setTimeout(() => {
                    window.location.reload();
                  }, 2000);
                } else {
                  ElementPlus.ElMessage({
                    message: response.data.error || 'Có lỗi xảy ra',
                    type: 'error',
                    duration: 3000
                  });
                }
              }

            } catch (error) {

            }
          });
        } catch (error) {
          rejectLoading.value = false;
          ElementPlus.ElMessage({
            message: error.message || 'Có lỗi xảy ra khi từ chối',
            type: 'error'
          });
        }
      };

      const checkEnabelSelect = () => {
        return form.value.proposer && form.value.manager && form.value.team_ms
      }

      const deleteRow = (index, program) => {
        tableDataKpi.value.splice(index, 1)
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
          tableDataKpi.value.push(newRow);
        } else {
          ElementPlus.ElNotification({
            message: 'Chương trình này đã được tạo!',
            type: 'warning',
            duration: 2000,
            position: 'top-right'
          });
        }
      }

      const handleAddKPI = async (rowData) => {
        showFormKPI.value = !showFormKPI.value;
        const stageInfo = rowData.reviewers.find(reviewer => reviewer.stage_id.toString() === rowData.stage_id);
        const stageLabel = stageInfo ? stageInfo.stage_label : '';
        const hasKpi = stageInfo ? stageInfo.has_kpi : '';

        form.value = {
          stage: stageLabel,
          stage_id: rowData.stage_id,
          max_stage: rowData.max_stage,
          list_propose: rowData.list_propose.split(', '),
          ms_list_id: rowData.id,
          user_id: rowData.user_id,
          user_name: rowData.user_name,
          user_email: rowData.user_email,
          employee_id: rowData.employee_id,
          department: rowData.department,
          type_ms: rowData.type_ms,
          type_ms_id: rowData.type_ms_id,
          propose: rowData.propose,
          team_ms: rowData.team_ms,
          team_ms_id: rowData.team_ms_id,
          has_kpi: hasKpi,
          reviewers: rowData.reviewers,
          agree_kpi: false,
          received_all: false,
          kpi: ''
        };

        if (hasKpi) {
          tableDataKpi.value = [];
          await getUserKpi(rowData.id, rowData.user_id, rowData.stage_id);
        }
        if (Number(rowData.stage_id) === Number(rowData.max_stage)) {
          await getListProposer();
          tableDataKpi.value = [];
          listProposer.value = listProposer.value.filter(item => Number(item.require_kpi) === 1 && Number(item.stage_id) != Number(rowData.max_stage));
          flag.value = true;
        }
      }

      const isFormValid = computed(() => {
        if (Array.isArray(form.list_propose) && form.list_propose[0] !== '') {
          return form.value.agree_kpi && form.value.received_all;
        }
        return form.value.agree_kpi;
      });

      const getTableDataKpi = async (ms_list_id, user_id, stage_id) => {
        await getUserKpi(ms_list_id, user_id, stage_id);
      }

      const getListProposer = async () => {
        try {
          const response = await axios.get(`../../api/get_list_proposer.php`);
          const data = response.data;
          if (data.success && data.data) {
            listProposer.value = data.data;
          } else {
            console.error('API Error:', data.message);
            listProposer.value = [];
          }
        } catch (error) {
          console.error('Error fetching:', error);
          listProposer.value = [];
        }
      }

      const getUserKpi = async (ms_list_id, user_id, stage_id) => {
        tableDataKpi.value = [];
        try {
          const response = await axios.get(`../../api/get_user_kpi.php`, {
            params: {
              ms_list_id: ms_list_id,
              user_id: user_id,
              stage_id: stage_id
            }
          });
          const data = response.data;
          if (data.success && data.data) {
            data.data.forEach(element => {
              JSON.parse(element.kpi).forEach(element => {
                if (element.program) {
                  const newRow = {
                    program: element.program,
                  };
                  for (let i = 1; i <= 12; i++) {
                    newRow[`m${i}`] = element[`m${i}`];
                  }
                  tableDataKpi.value.push(newRow);
                }
              });

            });
            tableDataKpi.value.forEach(element => {
              createdProgram.value.push(element.program);
            });
          } else {
            console.error('API Error:', data.message);
            tableDataKpi.value = [];
          }
        } catch (error) {
          console.error('Error fetching:', error);
          tableDataKpi.value = [];
        }
      }

      const getMsSignupList = async (offset = 0) => {
        try {
          const response = await axios.get(`../../api/get_ms_signup_list.php`, {
            params: {
              limit: pageSize.value,
              offset: offset
            }
          });
          const data = response.data;
          data.data.forEach(element => {
            if (element.list_propose) {
              element.list_propose = JSON.parse(element.list_propose).join(', ');
            }
            element.created_at = new Date(element.created_at).toLocaleDateString('vi-VN');
          });
          if (data.success && data.data) {
            tableData.value = data.data;
            total.value = data.total;
          } else {
            console.error('API Error:', data.message);
            tableData.value = [];
          }
        } catch (error) {
          console.error('Error fetching:', error);
          tableData.value = [];
        }
      };

      const handlePageChange = async (page) => {
        currentPage.value = page;
        await getMsSignupList((page - 1) * pageSize.value);
      };

      const pagination = computed(() => {
        const pages = Math.ceil(total.value / 50);
        const currentPage = currentPage.value;
        const pagination = [];
        for (let i = 1; i <= pages; i++) {
          pagination.push({
            page: i,
            active: i === currentPage
          });
        }
        return pagination;
      });

      const handleInputChange = (index, month, value) => {
        isEdit.value = true;
        tableDataKpi.value[index][`m${month}`] = value;
      }

      const handleCreateKpi = async ($flag = true) => {
        try {
          loading.value = $flag;
          form.value.kpi = tableDataKpi.value;
          const response = await axios.post(`../../api/create_kpi.php`, form.value);
          if (response.data.success) {
            ElementPlus.ElMessage({
              message: 'Tạo KPI thành công',
              type: 'success'
            });
            window.location.reload();
          } else {
            loading.value = false;
            throw new Error(response.data.message || 'Có lỗi xảy ra');
          }
        } catch (error) {
          loading.value = false;
          tableData.value = {};
          ElementPlus.ElMessage({
            message: error.message || 'Có lỗi xảy ra khi tạo KPI',
            type: 'error'
          });
        }
      }

      const validateTableKpiData = () => {
        if (tableDataKpi.value.length === 0) {
          return "Vui lòng nhập KPI cho ít nhất 1 chương trình";
        }
        const list = [];
        for (let i = 0; i < tableDataKpi.value.length; i++) {
          const row = tableDataKpi.value[i];

          let hasData = false;
          for (let j = 1; j <= 12; j++) {
            if (row[`m${j}`] !== "" && Number(row[`m${j}`]) !== 0) {
              hasData = true;
              break;
            }
          }

          if (!hasData) {
            list.push(row.program);
          }
        }
        if (list.length > 0) {
          if (list.length === 1) {
            return `Vui lòng nhập KPI cho chương trình ${list[0]}`;
          } else {
            return `Vui lòng nhập KPI cho các chương trình: ${list.join(', ')}`;
          }
        }

        return true;
      };

      const finalSubmit = async () => {
        try {
          const response = await axios.post('../../api/final_confirm.php', form.value);

        } catch (error) {

        }
      }
      onMounted(async () => {
        await getMsSignupList();
        allData.value = tableData.value;
      });

      const submitForm = async ($type) => {
        $data = {
          id: form.value.ms_list_id,
          stage_id: form.value.stage_id,
          user_name: form.value.user_name,
          user_email: form.value.user_email,
          employee_id: form.value.employee_id,
          department: form.value.department,
          type_ms: form.value.type_ms,
          team_ms: form.value.team_ms,
          propose: form.value.propose,
        };
        const flag = validateTableKpiData();
        if (flag !== true) {
          ElementPlus.ElMessage({
            message: flag,
            type: 'error'
          });
          return;
        }
        switch ($type) {
          case 'approve':
            if (isEdit.value) {
              await handleCreateKpi();
            }
            await handleApprove($data);
            break;
          case 'create kpi':
            await handleCreateKpi();
            break;
          case 'create and approve':
            await handleCreateKpi(false);
            await handleApprove($data);
            break;
          default:
            console.error('Invalid type:', $type);
        }

      };

      return {
        userId,
        tableData,
        tableDataKpi,
        listProposer,
        handleApprove,
        handleReject,
        showFormKPI,
        checkEnabelSelect,
        form,
        deleteRow,
        handleAddKPI,
        onAddItem,
        listProgram,
        handleInputChange,
        submitForm,
        urlUserInfo,
        isEdit,
        urlTeamMSInfo,
        getTableDataKpi,
        flag,
        currMonth,
        rejectLoading,
        approveLoading,
        loading,
        currentPage,
        pageSize,
        total,
        handlePageChange,
        handleSearch,
        searchQuery,
        isFormValid,
        finalSubmit
      }
    }
  });

  app.use(ElementPlus);
  app.mount('#ms_processes');
</script>