<script>
  const {
    createApp,
    ref,
    onMounted,
    computed
  } = Vue;

  const app = createApp({
    setup() {
      const flag = ref(false);
      const editKpiMSA = ref(false);
      const editKpiHR = ref(false);
      const form = ref({});
      const tableData = ref([]);
      const tableDataKpi = ref([]);
      const tableDataKpiMSA = ref([]);
      const tableDataKpiHR = ref([]);
      const oldDataKpiMSA = ref([]);
      const oldDataKpiHR = ref([]);
      const showFormKPI = ref(false);
      const createdProgram = ref([]);
      const createdProgramMSA = ref([]);
      const createdProgramHR = ref([]);
      const selectedProgram = ref('');
      const listProposer = ref([]);
      const allData = ref([]);
      const count = ref({});
      const noMore = ref({});
      const timelineData = ref({});
      const timelineLoading = ref(false);
      const activeNames = ref(['1']);
      const hasTimeline = ref(true);
      const stageDeal = ref([]);
      const currMonth = ref(new Date().getMonth() + 1);
      const currYear = ref(new Date().getFullYear());
      const isEdit = ref(false);
      const loading = ref(false);
      const tableLoading = ref(false);
      const rejectLoading = ref(false);
      const approveLoading = ref(false);
      const currentPage = ref(1);
      const pageSize = ref(50);
      const total = ref(0);
      const searchQuery = ref('');
      const activeName = ref('pending');
      let userId = <?= json_encode($userID ?? "") ?>;
      let listProgram = <?= json_encode($program ?? "") ?>;
      let urlUserInfo = <?= json_encode($config["url_user_info"] ?? "") ?>;
      let urlTeamMSInfo = <?= json_encode($config["url_team_ms_info"] ?? "") ?>;
      const pageTitle = `Danh sách đơn đăng ký làm MS`;
      const agreeKpiText = `Tôi đồng ý với các KPI được phân công ở bảng trên`;
      const agreeReceivedText = `Tôi đã nhận đủ các phần yêu cầu sau:`;
      const textBtn1 = `Xác nhận`;
      const textBtn2 = `Xét duyệt`;
      const textBtn3 = `Gửi yêu cầu`;
      const textBtn4 = `Điều chỉnh KPI`;
      const yearText = computed(() => {
        return `<span>${currYear.value}</span>`
      })
      const totalText = computed(() => {
        return `<span>TỔNG:</span>
          <span style="font-weight: bold; margin-left: 4px">${total.value}</span>`;
      });

      const linkProposerText = computed(() => {
        return form.value.user_name;
      });

      const linkTeamMSText = computed(() => {
        return form.value.team_ms;
      });

      const listText = computed(() => {
        const listItems = form.value.list_propose
          .map(item => `<li style="margin: 5px 0;">${item.trim()}</li>`)
          .join('');

        return `<div style="margin-left: 24px; margin-top: 10px;">
                <ul>
                    ${listItems}
                </ul>
            </div>`;
      });

      const handleChange = (val, stageId) => {
        activeNames.value = val;
        if (val.includes(stageId)) {
          if (!timelineData.value[stageId] || timelineData.value[stageId].length === 0) {
            load(stageId);
          }
        }
      }

      const toRaw = (value) => {
        if (value && typeof value === 'object' && value.hasOwnProperty('__v_raw')) {
          return value.__v_raw;
        }
        return value;
      };

      const load = async (stageId) => {
        if (loading.value || (noMore.value[stageId])) return;
        loading.value = true;

        try {
          if (!timelineData.value[stageId]) {
            timelineData.value[stageId] = [];
          }
          if (!count.value[stageId]) {
            count.value[stageId] = 0;
          }

          const response = await axios.get('../../api/get_list_modified_kpi.php', {
            params: {
              stage_id: stageId,
              ms_list_id: form.value.ms_list_id,
              user_id: form.value.user_id,
              offset: count.value[stageId],
              limit: 3
            }
          });

          if (response.data.success) {
            const currentData = timelineData.value[stageId].map(item => {
              const rawItem = toRaw(item);
              return {
                ...rawItem,
                tableData: rawItem.kpi_data || [],
                timestamp: rawItem.timestamp
              };
            });

            const newData = response.data.data.map(item => ({
              ...item,
              tableData: item.kpi_data || [],
              timestamp: item.timestamp
            }));

            timelineData.value[stageId] = [...currentData, ...newData];
            count.value[stageId] += newData.length;
            noMore.value[stageId] = count.value[stageId] >= response.data.total;
          }
        } catch (error) {
          console.error('Error loading timeline data:', error);
          ElementPlus.ElMessage({
            message: 'Có lỗi xảy ra khi tải dữ liệu',
            type: 'error'
          });
        } finally {
          loading.value = false;
        }
      };

      const resetTimeline = (stageId) => {
        timelineData.value[stageId] = [];
        count.value[stageId] = 0;
        noMore.value[stageId] = false;
      };

      const disabled = computed(() => {
        return (stageId) => {
          return loading.value || noMore.value[stageId];
        };
      });

      const isDisabled = (stageId) => {
        return loading.value || noMore.value[stageId];
      };

      const debounce = (fn, delay) => {
        let timeout;

        return (...args) => {
          if (timeout) clearTimeout(timeout);

          timeout = setTimeout(() => {
            fn.apply(this, args);
          }, delay);
        };
      };

      const handleSearch = async (type) => {
        currentPage.value = 1;
        let arFilter = null;
        switch (type) {
          case 'pending':
            arFilter = {
              status: 'pending'
            };
            break;
          case 'approved':
            arFilter = {
              status: 'success'
            };
            break;
          case 'rejected':
            arFilter = {
              status: 'error'
            };
            break;
          default:
            break;
        }
        if (arFilter) {
          await getMsSignupList(0, arFilter);
        }
      };

      const debouncedSearch = debounce((type) => {
        handleSearch(type);
      }, 500);

      const handleApprove = async (row, paramsApprove = null) => {
        try {
          approveLoading.value = true;
          let params = null;
          if (paramsApprove) {
            params = paramsApprove;
          } else {
            params = {
              id: row.id,
              stage_id: row.stage_id,
              user_name: row.user_name,
              user_email: row.user_email,
              employee_id: row.employee_id,
              department: row.department,
              type_ms: row.type_ms,
              team_ms: row.team_ms,
              propose: row.list_propose.split(/, (?![^(]*\))/)
            };
          }

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
                user_id: row.user_id,
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

      const deleteRow = (index, program, table = null) => {
        switch (table) {
          case 'MSA':
            tableDataKpiMSA.value.splice(index, 1)
            createdProgramMSA.value.splice(createdProgramMSA.value.indexOf(program), 1)
            break;
          case 'HR':
            tableDataKpiHR.value.splice(index, 1)
            createdProgramHR.value.splice(createdProgramHR.value.indexOf(program), 1)
            break;
          default:
            tableDataKpi.value.splice(index, 1)
            createdProgram.value.splice(createdProgram.value.indexOf(program), 1)
            break;
        }
      }

      const changeKpi = (proposer) => {
        flag.value = false;
        switch (proposer.label) {
          case 'MSA':
            oldDataKpiMSA.value = tableDataKpiMSA.value.map(row => ({
              ...row
            }));
            editKpiMSA.value = true;
            break;
          case 'HR':
            oldDataKpiHR.value = tableDataKpiHR.value.map(row => ({
              ...row
            }))
            editKpiHR.value = true;
            break;
        }
        stageDeal.value.includes(proposer.stage_id) ? '' : stageDeal.value.push(proposer.stage_id);
      }

      const onAddItem = (program, table) => {
        switch (table) {
          case 'MSA':
            if (!createdProgramMSA.value.includes(program)) {
              createdProgramMSA.value.push(program)
              const newRow = {
                program: program,
              };
              for (let i = 1; i <= 12; i++) {
                newRow[`m${i}`] = '';
              }
              tableDataKpiMSA.value.push(newRow);
            } else {
              ElementPlus.ElNotification({
                message: 'Chương trình này đã được tạo!',
                type: 'warning',
                duration: 2000,
                position: 'top-right'
              });
            }
            break;
          case 'HR':
            if (!createdProgramHR.value.includes(program)) {
              createdProgramHR.value.push(program)
              const newRow = {
                program: program,
              };
              for (let i = 1; i <= 12; i++) {
                newRow[`m${i}`] = '';
              }
              tableDataKpiHR.value.push(newRow);
            } else {
              ElementPlus.ElNotification({
                message: 'Chương trình này đã được tạo!',
                type: 'warning',
                duration: 2000,
                position: 'top-right'
              });
            }
            break;
          default:
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

      }

      const handleAddKPI = async (rowData) => {
        showFormKPI.value = !showFormKPI.value;
        if (showFormKPI.value) {
          document.body.style.overflow = 'hidden';
        }
        const stageInfo = rowData.reviewers.find(reviewer => reviewer.stage_id.toString() === rowData.stage_id);
        const hasKpi = stageInfo ? stageInfo.has_kpi : '';
        const stageLabel = stageInfo ? stageInfo.stage_label : '';
        form.value = {
          status: rowData.status,
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
          department_ids: rowData.department_id,
          type_ms: rowData.type_ms,
          type_ms_id: rowData.type_ms_id,
          propose: rowData.propose,
          team_ms: rowData.team_ms,
          team_ms_id: rowData.team_ms_id,
          has_kpi: hasKpi,
          reviewers: rowData.reviewers,
          process_deal: rowData.process_deal,
          agree_kpi: false,
          received_all: false,
          year: currMonth.value === 12 ? currYear.value + 1 : currYear.value,
          kpi: ''
        };

        if (hasKpi) {
          tableDataKpi.value = [];
          await getUserKpi(rowData.id, rowData.user_id, rowData.stage_id);
        }
        if (Number(rowData.stage_id) === Number(rowData.max_stage)) {
          flag.value = true;
          await getListProposer();
          listProposer.value = listProposer.value.filter(item => Number(item.require_kpi) === 1 && Number(item.stage_id) != Number(rowData.max_stage));
          const promises = listProposer.value.map(element => {
            if (element.label == 'MSA') {
              return getUserKpi(rowData.id, rowData.user_id, element.stage_id, element.label);
            } else if (element.label == 'HR') {
              return getUserKpi(rowData.id, rowData.user_id, element.stage_id, element.label);
            }
          });
          await Promise.all(promises);
        }
      }

      const isFormValid = computed(() => {
        return form.value.agree_kpi;
      });

      const getTableDataKpi = async (ms_list_id, user_id, stage_id) => {
        await getUserKpi(ms_list_id, user_id, stage_id, tableDataKpi);
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

      const getUserKpi = async (ms_list_id, user_id, stage_id, type = "main") => {
        let baseTable = [];
        switch (type) {
          case 'main':
            baseTable = tableDataKpi;
            break;
          case 'MSA':
            baseTable = tableDataKpiMSA;
            break;
          case 'HR':
            baseTable = tableDataKpiHR;
            break;
        }
        baseTable.value = [];
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
                  baseTable.value.push(newRow);
                }
              });

            });
            baseTable.value.forEach(element => {
              switch (type) {
                case 'main':
                  createdProgram.value.push(element.program);
                  break;
                case 'MSA':
                  createdProgramMSA.value.push(element.program);
                  break;
                case 'HR':
                  createdProgramHR.value.push(element.program);
                  break;
              }
            });
          } else {
            console.error('API Error:', data.message);
            baseTable.value = [];
          }
        } catch (error) {
          console.error('Error fetching:', error);
          baseTable.value = [];
        }
      }

      const getMsSignupList = async (offset = 0, $arFilter = null) => {
        try {
          tableLoading.value = true;
          const response = await axios.get(`../../api/get_ms_signup_list.php`, {
            params: {
              limit: pageSize.value,
              offset: offset,
              searchQuery: searchQuery.value,
              arFilter: $arFilter
            }
          });
          const data = response.data;
          data.data.forEach(element => {
            if (element.list_propose != "") {
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
        } finally {
          tableLoading.value = false;
        }
      };

      const handlePageChange = async (page) => {
        currentPage.value = page;
        let arFilter = null;
        switch (activeName.value) {
          case 'pending':
            arFilter = {
              status: 'pending'
            };
            break;
          case 'approved':
            arFilter = {
              status: 'success'
            };
            break;
          case 'rejected':
            arFilter = {
              status: 'error'
            };
            break;
          default:
            break;
        }
        if (arFilter) {
          await getMsSignupList((page - 1) * pageSize.value, arFilter);
        }
      };

      const handleSizeChange = (val) => {
        pageSize.value = val;
        handlePageChange(1);
      }

      const pagination = computed(() => {
        const pages = Math.ceil(total.value / pageSize.value);
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
          if (!$flag) {
            loading.value = $flag;
            approveLoading.value = true;
          } else {
            loading.value = true;
          }
          form.value.kpi = tableDataKpi.value;
          const response = await axios.post(`../../api/create_kpi.php`, form.value);
          if (response.data.success) {
            ElementPlus.ElMessage({
              message: 'Tạo KPI thành công',
              type: 'success'
            });
            if ($flag) {
              window.location.reload();
            }
          } else {
            loading.value = false;
            approveLoading.value = false;
            throw new Error(response.data.message || 'Có lỗi xảy ra');
          }
        } catch (error) {
          loading.value = false;
          approveLoading.value = false;
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
            if (row[`m${j}`] !== "" && row[`m${j}`] > 0) {
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
            return `Vui lòng nhập KPI cho chương trình ${list[0]} là số nguyên dương`;
          } else {
            return `Vui lòng nhập KPI cho các chương trình: ${list.join(', ')} là số nguyên dương`;
          }
        }

        return true;
      };

      const validateTableKpiMSAData = () => {
        if (tableDataKpiMSA.value.length === 0) {
          return "Vui lòng nhập KPI cho ít nhất 1 chương trình tại bảng KPI MSA";
        }
        const list = [];
        for (let i = 0; i < tableDataKpiMSA.value.length; i++) {
          const row = tableDataKpiMSA.value[i];

          let hasData = false;
          for (let j = 1; j <= 12; j++) {
            if (row[`m${j}`] !== "" && row[`m${j}`] > 0) {
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
            return `Vui lòng nhập KPI cho chương trình ${list[0]} tại bảng KPI MSA là số nguyên dương`;
          } else {
            return `Vui lòng nhập KPI cho các chương trình: ${list.join(', ')} tại bảng KPI MSA là số nguyên dương`;
          }
        }

        return true;
      };

      const validateTableKpiHRData = () => {
        if (tableDataKpiHR.value.length === 0) {
          return "Vui lòng nhập KPI cho ít nhất 1 chương trình tại bảng KPI HR";
        }
        const list = [];
        for (let i = 0; i < tableDataKpiHR.value.length; i++) {
          const row = tableDataKpiHR.value[i];

          let hasData = false;
          for (let j = 1; j <= 12; j++) {
            if (row[`m${j}`] !== "" && row[`m${j}`] > 0) {
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
            return `Vui lòng nhập KPI cho chương trình ${list[0]} tại bảng KPI HR là số nguyên dương`;
          } else {
            return `Vui lòng nhập KPI cho các chương trình: ${list.join(', ')} tại bảng KPI HR là số nguyên dương`;
          }
        }

        return true;
      };

      const finalSubmit = async () => {
        try {
          loading.value = true;
          const response = await axios.post('../../api/final_confirm.php', form.value);
          if (response.data.success) {
            ElementPlus.ElMessage({
              message: 'Xác nhận thành công',
              type: 'success'
            });
            window.location.reload();
          } else {
            loading.value = false;
            ElementPlus.ElMessage({
              message: response.data.error || 'Có lỗi xảy ra',
              type: 'error',
              duration: 3000
            });
          }

        } catch (error) {
          loading.value = false;
          ElementPlus.ElMessage({
            message: error.message || 'Có lỗi xảy ra',
            type: 'error',
            duration: 3000
          });
        }
      }

      const hideOverlay = () => {
        if (!loading.value && !approveLoading.value) {
          showFormKPI.value = false;
          document.body.style.overflow = 'auto';
          editKpiHR.value = false;
          editKpiMSA.value = false;
          isEdit.value = false;
          activeNames.value = [];
          listProposer.value.forEach((item) => {
            resetTimeline(item.stage_id);
          });
        }
      }

      const checkDisabled = (month) => {
        if (month <= currMonth.value && form.value.year === currYear.value) {
          return true;
        }
        return false;
      }

      const handleClick = async (tab, event) => {
        activeName.value = tab.props.name;
        let arFilter = null;
        switch (tab.props.name) {
          case 'pending':
            arFilter = {
              status: 'pending'
            };
            break;
          case 'approved':
            arFilter = {
              status: 'success'
            };
            break;
          case 'rejected':
            arFilter = {
              status: 'error'
            };
            break;
          default:
            break;
        }
        if (arFilter) {
          await getMsSignupList(0, arFilter);
        }
      };

      const handleDealKpi = async () => {
        form.value.stage_deal = JSON.stringify(stageDeal.value);
        form.value.kpi_hr = JSON.stringify(tableDataKpiHR.value);
        form.value.kpi_msa = JSON.stringify(tableDataKpiMSA.value);
        form.value.old_kpi_hr = JSON.stringify(oldDataKpiHR.value);
        form.value.old_kpi_msa = JSON.stringify(oldDataKpiMSA.value);
        if (validateTableKpiMSAData() !== true) {
          ElementPlus.ElMessage({
            message: validateTableKpiMSAData(),
            type: 'error'
          })
          return;
        }
        if (validateTableKpiHRData() !== true) {
          ElementPlus.ElMessage({
            message: validateTableKpiHRData(),
            type: 'error'
          })
          return;
        }
        try {
          await handleCreateKpi(false);
          const params = {
            id: form.value.ms_list_id,
            user_name: form.value.user_name,
            user_email: form.value.user_email,
            employee_id: form.value.employee_id,
            department: form.value.department,
            type_ms: form.value.type_ms,
            team_ms: form.value.team_ms,
            propose: form.value.list_propose,
            max_stage: form.value.max_stage,
            stage_deal: JSON.stringify(stageDeal.value),
          };
          await handleApprove(null, params);

        } catch (error) {
          ElementPlus.ElMessage({
            message: error.message || 'Có lỗi xảy ra',
            type: 'error'
          });
        }
      }

      onMounted(async () => {
        await getMsSignupList(0, arFilter = {
          status: 'pending'
        });
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
          list_propose: form.value.list_propose.join(', '),
        };
        const flag = validateTableKpiData();
        if (flag !== true) {
          ElementPlus.ElMessage({
            message: flag,
            type: 'error'
          });
          return;
        }
        if (isEdit.value) {
          await handleCreateKpi();
        }
        await handleApprove($data);

      };

      return {
        userId,
        tableData,
        tableDataKpi,
        tableDataKpiMSA,
        tableDataKpiHR,
        listProposer,
        handleApprove,
        handleReject,
        showFormKPI,
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
        flag,
        currMonth,
        rejectLoading,
        approveLoading,
        loading,
        currentPage,
        pageSize,
        total,
        handlePageChange,
        debouncedSearch,
        searchQuery,
        isFormValid,
        finalSubmit,
        hideOverlay,
        handleSizeChange,
        tableLoading,
        pageTitle,
        totalText,
        agreeKpiText,
        agreeReceivedText,
        textBtn1,
        textBtn2,
        textBtn3,
        textBtn4,
        listText,
        linkProposerText,
        linkTeamMSText,
        checkDisabled,
        activeName,
        handleClick,
        yearText,
        changeKpi,
        editKpiMSA,
        editKpiHR,
        handleDealKpi,
        count,
        noMore,
        timelineData,
        timelineLoading,
        activeNames,
        hasTimeline,
        load,
        handleChange,
        isDisabled
      }
    }
  });

  app.use(ElementPlus);
  app.mount('#ms_processes');
</script>