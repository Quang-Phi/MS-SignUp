<script>
  const {
    createApp,
    ref,
    onMounted,
    computed,
    watch
  } = Vue;

  const app = createApp({
    setup() {
      const flag = ref(false);
      const editKpiMSA = ref(false);
      const editKpiHR = ref(false);
      const form = ref({});
      const tableRef = ref(null);
      const tableData = ref([]);
      const tableDataKpi = ref([]);
      const tableDataKpiMSA = ref([]);
      const tableDataKpiHR = ref([]);
      const oldDataKpiMSA = ref([]);
      const oldDataKpiHR = ref([]);
      const tableDataKpiMemberHR = ref([]);
      const tableDataKpiMemberMSA = ref([]);
      const showFormKPI = ref(false);
      const createdProgram = ref([]);
      const createdProgramMSA = ref([]);
      const createdProgramHR = ref([]);
      const addProgram = ref([]);
      const selectedProgram = ref('');
      const listProposer = ref([]);
      const teamMsMember = ref([]);
      const historyDataKpi = ref([]);
      const historyDataKpiMSA = ref([]);
      const historyDataKpiHR = ref([]);
      const count = ref({});
      const noMore = ref({});
      const timelineData = ref({});
      const timelineLoading = ref(false);
      const activeNames = ref('pending');
      const hasTimeline = ref(true);
      const stageDeal = ref([]);
      // const currMonth = ref(new Date().getMonth() + 1);
      // const currYear = ref(new Date().getFullYear());
      const currMonth = ref(1);
      const currYear = ref(2028);
      const nextYear = ref(new Date().getFullYear() + 1);
      const isEdit = ref(false);
      const loading = ref(false);
      const tableLoading = ref(false);
      const rejectLoading = ref({});
      const approveLoading = ref({});
      const loadingHistory = ref(false);
      const loadingKPI = ref(false);
      const loadingMemberKPI = ref(false);
      const currentPage = ref(1);
      const pageSize = ref(50);
      const total = ref(0);
      const errYear = ref(0);
      const searchQuery = ref('');
      const activeName = ref('pending');
      const activeNameKpi = ref(null);
      const showKPI = ref(false);
      let userId = <?= json_encode($userID ?? "") ?>;
      let listProgram = <?= json_encode($program ?? "") ?>;
      let urlUserInfo = <?= json_encode($config["url_user_info"] ?? "") ?>;
      let urlTeamMSInfo = <?= json_encode($config["url_team_ms_info"] ?? "") ?>;
      let hrIds = <?= json_encode($config["hr_ids"] ?? "") ?>;
      let msaIds = <?= json_encode($config["msa_ids"] ?? "") ?>;
      let coefficients = <?= json_encode($config["coefficients"] ?? "") ?>;
      const pageTitle = `Danh sách đơn đăng ký tham gia hoạt động MS`;
      const agreeKpiText = `Tôi đồng ý với các KPI được phân công ở bảng trên`;
      const agreeReceivedText = `Tôi đã nhận đủ các phần yêu cầu sau:`;
      const textBtn1 = `Xác nhận`;
      const textBtn2 = `Xét duyệt`;
      const textBtn3 = `Gửi yêu cầu`;
      const timelineLoadingText = `Đang lấy dữ liệu...`;
      const noMoreText = `Không còn dữ liệu`;
      const textBtn4 = `Điều chỉnh KPI`;
      const textReviewerName = computed(() => {
        return `<span>${form.value.stage}</span>`
      })

      const textReviewerName2 = computed(() => {
        return (proposer) => {
          const reviewer = form.value.reviewers.find(r => r.stage_id == proposer.stage_id);
          return `<span>${reviewer?.stage_label || ''}</span>`;
        }
      });

      const yearText = computed(() => {
        return `<span>${currMonth.value === 12  && form.value.status === 'pending' ? nextYear.value : form.value.status === 'error'? errYear.value : currYear.value}</span>`
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

      const btnKpisMemberText = computed(() => {
        return `KPIs thành viên nhóm ${form.value.team_ms}`;
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

      const showNotification = (type, message) => {
        ElementPlus.ElNotification({
          type: type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'error',
          message: message,
          duration: 2000,
          position: 'top-right'
        });
      };

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
        if (loadingHistory.value || (noMore.value[stageId])) return;
        loadingHistory.value = true;

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
          console.error('Error loadingHistory timeline data:', error);
          showNotification('error', 'Có lỗi xảy ra.');
        } finally {
          loadingHistory.value = false;
        }
      };

      const resetTimeline = (stageId) => {
        timelineData.value[stageId] = [];
        count.value[stageId] = 0;
        noMore.value[stageId] = false;
      };

      const disabled = computed(() => {
        return (stageId) => {
          return loadingHistory.value || noMore.value[stageId];
        };
      });

      const isDisabled = (stageId) => {
        return loadingHistory.value || noMore.value[stageId];
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

      const reloadPage = () => {
        setTimeout(() => {
          window.location.href = "<?php echo $config['base_url']; ?>/<?php echo $config['root_folder']; ?>/form/list/";
        }, 1000);
      }

      const handleApprove = async (row, paramsApprove = null, message = true, reload = false) => {
        try {
          if (row?.id) {
            approveLoading.value[row.id] = true;
          }
          let params = null;
          if (paramsApprove) {
            params = paramsApprove;
          } else {
            params = {
              ...row,
              propose: row.list_propose.split(/, (?![^(]*\))/)
            };
          }

          const response = await axios.post('../../api/approve_ms_register.php', params);

          if (response.data.success) {
            message && showNotification('success', 'Xét duyệt thành công');
            reload && reloadPage();
            return true;
          } else {
            if (response.data.code === 'STAGE_MISMATCH') {
              showNotification('warning', 'Yêu cầu này đã được xử lý. Trang sẽ được tải lại.');
              reload && reloadPage();
              return true;
            } else {
              if (row?.id) {
                approveLoading.value[row.id] = false;
              }
              loading.value = false;
              loadingKPI.value = false;
              showNotification('error', 'Có lỗi xảy ra khi xét duyệt');
              return false;
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
          loading.value = false;
          loadingKPI.value = false;
          showNotification('error', errorMessage);
          return false;
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
            rejectLoading.value[row.id] = true;
            try {
              const response = await axios.post('../../api/reject_ms_register.php', {
                ...row,
                comments: value,
                reviewer: row.reviewers.find(reviewer => reviewer.stage_id.toString() === row.stage_id)?.stage_label
              });
              if (response.data.success) {
                showNotification('success', 'Đã từ chối đơn đăng ký.');
                reloadPage();
              } else {
                rejectLoading.value[row.id] = false;
                if (response.data.code === 'STAGE_MISMATCH') {
                  showNotification('warning', 'Yêu cầu này đã được xử lý. Trang sẽ được tải lại.');
                  reloadPage();
                } else {
                  showNotification('error', 'Có lỗi xảy ra.');
                }
              }

            } catch (error) {

            }
          });
        } catch (error) {
          rejectLoading.value = false;
          showNotification('error', 'Có lỗi xảy ra.');
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

      const changeKpi = (proposer, form) => {
        flag.value = false;
        if (form.status === 'pending') {
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
          return true;
        } else {
          switch (proposer.label) {
            case 'MSA':
              editKpiMSA.value = true;
              oldDataKpiMSA.value = tableDataKpiMSA.value.map(row => ({
                ...row
              }));
              const reviewer = form.reviewers.find(r => r.stage_id === parseInt(proposer.stage_id));
              const reviewer_id = reviewer ? reviewer.reviewer_id : null;
              if (msaIds.includes(userId) || userId === reviewer_id) {
                stageDeal.value.includes(form.max_stage) ? '' : stageDeal.value.push(form.max_stage);
              } else {
                stageDeal.value.includes(proposer.stage_id) ? '' : stageDeal.value.push(proposer.stage_id);
              }
              break;
            case 'HR':
              editKpiHR.value = true;
              if (tableDataKpiHR.value.length < 1) {
                onAddItem('KPI', 'HR');
              } else {
                oldDataKpiHR.value = tableDataKpiHR.value.map(row => ({
                  ...row
                }))
              }
              stageDeal.value.includes(form.max_stage) ? '' : stageDeal.value.push(form.max_stage);
              break;
          }
        }
      }

      const checkShowDelete = (program) => {
        if (addProgram.value.includes(program)) {
          return true;
        } else {
          return false
        }
      }

      const onAddItem = (program, table) => {
        switch (table) {
          case 'MSA':
            if (!createdProgramMSA.value.includes(program)) {
              createdProgramMSA.value.push(program)
              addProgram.value.push(program)

              const newRow = {
                program: program,
              };
              for (let i = 1; i <= 12; i++) {
                newRow[`m${i}`] = 0;
              }
              tableDataKpiMSA.value.push(newRow);
            } else {
              showNotification('warning', 'Chương trình này đã được tạo!');
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
              showNotification('warning', 'Chương trình này đã được tạo!');
            }
            break;
          default:
            if (!createdProgram.value.includes(program)) {
              createdProgram.value.push(program);
              addProgram.value.push(program);
              const newRow = {
                program: program,
              };
              for (let i = 1; i <= 12; i++) {
                newRow[`m${i}`] = '';
              }
              tableDataKpi.value.push(newRow);
            } else {
              showNotification('warning', 'Chương trình này đã được tạo!');
            }
        }

      }

      const handleAddKPI = async (rowData) => {
        loadingKPI.value = true;
        tableDataKpi.value = [];
        tableDataKpiMSA.value = [];
        tableDataKpiHR.value = [];
        showFormKPI.value = !showFormKPI.value;
        if (showFormKPI.value) {
          document.body.style.overflow = 'hidden';
        }
        const stageInfo = rowData.reviewers.find(reviewer => reviewer.stage_id.toString() === rowData.stage_id);
        const hasKpi = stageInfo ? stageInfo.has_kpi : '';
        const stageLabel = stageInfo ? stageInfo.stage_label : '';
        form.value = {
          ...rowData,
          has_kpi: hasKpi,
          stage: stageLabel,
          stage_deal: rowData.process_deal,
          ms_list_id: rowData.id,
          agree_kpi: false,
          received_all: false,
          year: currYear.value,
          next_year: nextYear.value,
          curr_month: currMonth.value,
          department_ids: rowData.department_id,
          kpi: ''
        };

        let year = null;
        switch (rowData.status) {
          case 'pending':
            year = currMonth.value === 12 ? nextYear.value : currYear.value;
            break;
          case 'success':
            year = currYear.value;
            break;
        }
        if (Number(rowData.stage_id) === Number(rowData.max_stage)) {
          flag.value = true;
          await getListProposer();
          listProposer.value = listProposer.value.filter(item => Number(item.require_kpi) === 1 && Number(item.stage_id) != Number(rowData.max_stage));

          const promises = listProposer.value.map(element => {
            if (element.label == 'MSA') {
              return getUserKpi(rowData.id, rowData.user_id, element.stage_id, element.label, year);
            } else if (element.label == 'HR') {
              return getUserKpi(rowData.id, rowData.user_id, element.stage_id, element.label, year);
            }
          });
          const responses = await Promise.all(promises);
          if (currMonth.value === 12 && rowData.status === 'pending') {
            listProposer.value.forEach(element => {
              if (element.label == 'MSA' || element.label == 'HR') {
                switch (element.label) {
                  case 'MSA':
                    tableDataKpiMSA.value.length < 1 ? stageDeal.value.includes(element.stage_id) ? '' : stageDeal.value.push(element.stage_id) : '';
                    break;
                  case 'HR':
                    tableDataKpiHR.value.length < 1 ? stageDeal.value.includes(element.stage_id) ? '' : stageDeal.value.push(element.stage_id) : '';
                    break;
                }
              }
            })
            if (tableDataKpiMSA.value.length < 1 && tableDataKpiHR.value.length < 1) {
              form.value.create_history = true;
              handleDealKpi(true);
            }
          }
          loadingKPI.value = false;
          return;
        }

        if (hasKpi) {
          tableDataKpi.value = [];
          const res = await getUserKpi(rowData.id, rowData.user_id, rowData.stage_id, 'main', year);
        }

        if (form.value.stage_id == 4) {
          tableDataKpiMSA.value = [];
          await getUserKpi(rowData.id, rowData.user_id, 3, 'MSA', year);

          if (tableDataKpi.value.length == 0) {
            onAddItem('KPI');
          }
        }

        loadingKPI.value = false;
      }

      const isFormValid = computed(() => {
        return form.value.agree_kpi;
      });

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

      const getUserKpi = async (ms_list_id, user_id, stage_id, type = "main", year = null) => {
        let baseTable = [];
        let historyTable = null;
        switch (type) {
          case 'main':
            baseTable = tableDataKpi;
            historyTable = historyDataKpi
            break;
          case 'MSA':
            baseTable = tableDataKpiMSA;
            historyTable = historyDataKpiMSA
            break;
          case 'HR':
            baseTable = tableDataKpiHR;
            historyTable = historyDataKpiHR
            break;
          case 'member_HR':
            baseTable = tableDataKpiMemberHR;
            break;
          case 'member_MSA':
            baseTable = tableDataKpiMemberMSA;
            break;
        }
        try {
          const response = await axios.get(`../../api/get_user_kpi.php`, {
            params: {
              ms_list_id,
              user_id,
              stage_id,
              year
            }
          });
          const data = response.data;
          if (data.success && data.data) {
            errYear.value = data?.data[0]?.year;
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
                case 'member_HR':
                  break;
                case 'member_MSA':
                  break;
              }
            });

            if (data.history && historyTable) {
              data.history.forEach(element => {
                JSON.parse(element.old_kpi).forEach(element => {
                  if (element.program) {
                    const newRow = {
                      program: element.program,
                    };
                    for (let i = 1; i <= 12; i++) {
                      newRow[`m${i}`] = element[`m${i}`];
                    }
                    historyTable.value.push(newRow);
                  }
                });

              });

            }
            return true;
          } else {
            console.error('API Error:', data.message);
            baseTable.value = [];
            return false;
          }
        } catch (error) {
          console.error('Error fetching:', error);
          baseTable.value = [];
          return false;
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
            element.join_date = new Date(element.join_date).toLocaleDateString('vi-VN');
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

      const handleInputChange = (index, month, value, proposer) => {
        isEdit.value = true;
        const regex = /^[0-9.,]+$/;
        if (!regex.test(value) || isNaN(value)) {
          value = ''
        }
        if (proposer) {
          switch (proposer.label) {
            case 'MSA':
              if (!tableDataKpiMSA.value[index]) {
                tableDataKpiMSA.value[index] = {};
              }
              tableDataKpiMSA.value[index] = {
                ...tableDataKpiMSA.value[index],
                [`m${month}`]: value ? Math.round(value) : ''
              };
              break;
            case 'HR':
              const parts = value.split('.');
              if (parts.length > 1) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
              }
              if (!tableDataKpiHR.value[index]) {
                tableDataKpiHR.value[index] = {};
              }
              tableDataKpiHR.value[index] = {
                ...tableDataKpiHR.value[index],
                [`m${month}`]: value
              };
              break;
            default:
              if (!tableDataKpi.value[index]) {
                tableDataKpi.value[index] = {};
              }
              if (proposer.stage_id == 4) {
                const parts = value.split('.');
                if (parts.length > 1) {
                  value = parts[0] + '.' + parts[1].substring(0, 2);
                }
              }
              tableDataKpi.value[index] = {
                ...tableDataKpi.value[index],
                [`m${month}`]: proposer.stage_id == 4 ? value : value ? Math.round(value) : ''
              };
              break;
          }
        }
      };

      const handleCreateKpi = async () => {
        try {
          form.value.kpi = tableDataKpi.value;
          const response = await axios.post(`../../api/create_kpi.php`, form.value);
          if (response.data.success) {
            return true;
          } else {
            if (response.data.code === 'STAGE_MISMATCH') {
              return true;
            } else {
              showNotification('error', response.data.message || 'Có lỗi xảy ra khi tạo KPI');
              return false;
            }

          }
        } catch (error) {
          if (response.data.code === 'STAGE_MISMATCH') {
            return true;
          } else {
            tableData.value = {};
            showNotification('error', error.message || 'Có lỗi xảy ra khi tạo KPI');
            return false;
          }

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
          return "Vui lòng nhập KPI cho ít nhất 1 chương trình tại bảng KPI MSA.";
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
            return `Vui lòng nhập KPI cho chương trình ${list[0]} tại bảng KPI MSA là số dương`;
          } else {
            return `Vui lòng nhập KPI cho các chương trình: ${list.join(', ')} tại bảng KPI MSA là số dương`;
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
          loadingKPI.value = true;
          const response = await axios.post('../../api/final_confirm.php', form.value);
          if (response.data.success && form.value.completed == false) {
            showNotification('success', 'Đăng ký MS thành công.');
            reloadPage();
          } else if (response.data.success && form.value.completed == true) {
            showNotification('success', 'Xác nhận thành công');
            reloadPage();
          } else {
            loading.value = false;
            loadingKPI.value = false;
            if (response.data.code === 'STAGE_MISMATCH') {
              showNotification('warning', 'Yêu cầu này đã được xử lý. Trang sẽ được tải lại.');
              reloadPage();
            } else {
              showNotification('error', response.data.message || 'Có lỗi xảy ra khi đăng ký MS');
            }
          }

        } catch (error) {
          loading.value = false;
          loadingKPI.value = false;
          showNotification()
        }
      }

      const hideOverlay = () => {
        const isEmpty = Object.keys(approveLoading.value).length === 0;
        if (!loading.value && isEmpty) {
          showFormKPI.value = false;
          document.body.style.overflow = 'auto';
          editKpiHR.value = false;
          editKpiMSA.value = false;
          isEdit.value = false;
          activeNames.value = [];
          stageDeal.value = [];
          createdProgram.value = [];
          createdProgramHR.value = [];
          createdProgramMSA.value = [];
          listProposer.value.forEach((item) => {
            resetTimeline(item.stage_id);
          });
        }
      }

      const checkDisabled = (month) => {
        if (month <= currMonth.value && form.value.year === (currMonth.value === 12 ? nextYear.value : currYear.value)) {
          return true;
        }
        return false;
      }

      const handleClick = async (tab, event) => {
        activeName.value = tab.props.name;
        searchQuery.value = '';
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

      const handleDealKpi = async (resetArr = false) => {
        loading.value = true;
        loadingKPI.value = true;
        form.value.stage_deal = JSON.stringify(stageDeal.value);
        form.value.kpi_hr = JSON.stringify(tableDataKpiHR.value);
        form.value.kpi_msa = JSON.stringify(tableDataKpiMSA.value);
        form.value.old_kpi_hr = JSON.stringify(oldDataKpiHR.value);
        form.value.old_kpi_msa = JSON.stringify(oldDataKpiMSA.value);

        if (resetArr) {
          form.value.kpi_hr = [];
          form.value.kpi_msa = [];
        } else {
          if (form.value.status === 'pending' && form.value.completed == false) {
            if (validateTableKpiMSAData() !== true) {
              showNotification('error', validateTableKpiMSAData());
              loading.value = false;
              loadingKPI.value = false;
              return;
            }
            if (validateTableKpiHRData() !== true) {
              showNotification('error', validateTableKpiHRData());
              loading.value = false;
              loadingKPI.value = false;
              return;
            }
          }
          if (form.value.status === 'success' || form.value.completed == true) {
            editKpiMSA.value ? form.value.flag_edit_3 = true : null;
            if (editKpiHR.value) {
              form.value.flag_edit_4 = true;
              form.value.tempo_stage = 4;
            }
            if (editKpiMSA.value && validateTableKpiMSAData() !== true) {
              showNotification('error', validateTableKpiMSAData());
              loading.value = false;
              loadingKPI.value = false;
              return;
            }
            if (editKpiHR.value && validateTableKpiHRData() !== true) {
              showNotification('error', validateTableKpiHRData());
              loading.value = false;
              loadingKPI.value = false;
              return;
            }
          }
        }
        try {
          const res = await handleCreateKpi();
          if (res) {
            const params = {
              ...form.value,
              id: form.value.ms_list_id,
              propose: form.value.list_propose,
              stage_deal: JSON.stringify(stageDeal.value)
            }
            const res = await handleApprove(null, params, false);
            if (res === true) {
              resetArr ? showNotification('success', 'Chưa có KPI, đã gửi yêu cầu thêm KPI mới.') : showNotification('success', 'Gửi yêu cầu điều chỉnh thành công.');
              reloadPage();
            }
          }

        } catch (error) {
          loading.value = false;
          loadingKPI.value = false;
          showNotification('error', 'Có lỗi xảy ra.');
        }
      }

      const checkStep = (id) => {
        return Number(id) === 4;
      }

      const getSummaries = (param) => {
        const {
          columns,
          data
        } = param;
        const sums = [];

        columns.forEach((column, index) => {
          if (index === 0) {
            sums[index] = 'KPI';
            return;
          }

          if (!column.property || column.property === 'actions') {
            sums[index] = '';
            return;
          }

          const values = data.map(item => {
            const id = Object.keys(listProgram).find(key => listProgram[key] === item.program);
            const coefficient = coefficients[id] || 1;
            return (Number(item[column.property]) || 0) * coefficient;
          });

          if (!values.every(value => isNaN(value))) {
            sums[index] = values.reduce((prev, curr) => {
              const value = Number(curr);
              if (!isNaN(value)) {
                return prev + value;
              } else {
                return prev;
              }
            }, 0).toFixed(2);
          } else {
            sums[index] = '';
          }
        });

        return sums;
      };


      const calculateRowTotal = (row) => {
        let total = 0;
        for (let month = 1; month <= 12; month++) {
          total += Number(row[`m${month}`]) || 0;
        }
        return total;
      };

      const checkChangeKpi = (proposer, form) => {
        const owner = parseInt(form.user_id);
        const reviewer = form.reviewers.find(r => r.stage_id === parseInt(proposer.stage_id));
        const reviewer_id = reviewer ? reviewer.reviewer_id : null;
        switch (proposer.label) {
          case 'MSA':
            if (tableDataKpiMSA.value.length < 1) {
              return msaIds.includes(userId) || userId === reviewer_id || userId === owner;
            } else {
              return msaIds.includes(userId) || userId === reviewer_id;
            }
            break;
          case 'HR':
            if (hrIds.includes(userId) || userId === reviewer_id) {
              return true;
            }
            break;
        }
      }

      const getTeamMsMember = async () => {
        try {
          const response = await axios.get('../../api/get_team_ms_member.php', {
            params: {
              team_ms_id: form.value.team_ms_id
            }
          });
          if (response.data.success) {
            teamMsMember.value = response.data.data;
            return response.data.data;
          }
        } catch (error) {
          console.error(error);
        }
      }

      const showKpisMember = async () => {
        showKPI.value = true;
        loadingMemberKPI.value = true;
        tableDataKpiMemberHR.value = [];
        tableDataKpiMemberMSA.value = [];
        const res = await getTeamMsMember();
        if (res) {
          const promises = listProposer.value.map(element => {
            if (element.label == 'MSA') {
              return getUserKpi(null, res[0].ID, element.stage_id, 'member_MSA', currYear.value);
            } else if (element.label == 'HR') {
              return getUserKpi(null, res[0].ID, element.stage_id, 'member_HR', currYear.value);
            }
          });
          const responses = await Promise.all(promises);
        }
        activeNameKpi.value = res[0].ID;
        loadingMemberKPI.value = false;
      }

      const handleClickTab = async (tab, event) => {
        loadingMemberKPI.value = true;
        tableDataKpiMemberHR.value = [];
        tableDataKpiMemberMSA.value = [];
        activeNameKpi.value = tab.props.name;
        const promises = listProposer.value.map(element => {
          if (element.label == 'MSA') {
            return getUserKpi(null, tab.props.name, element.stage_id, 'member_MSA', currYear.value);
          } else if (element.label == 'HR') {
            return getUserKpi(null, tab.props.name, element.stage_id, 'member_HR', currYear.value);
          }
        });
        const responses = await Promise.all(promises);
        loadingMemberKPI.value = false;
      }

      const hideKPIsMember = () => {
        showKPI.value = false;
      }

      const checkViewerMemberKpi = (form) => {
        const arr = form.reviewers.filter(reviewer => parseInt(reviewer.stage_id) !== parseInt(form.max_stage));
        const reviewers = arr.map(reviewer => reviewer.reviewer_id);
        return msaIds.includes(userId) || hrIds.includes(userId) || reviewers.includes(userId);
      }

      const checkShowKPI = (reviewers, stage) => {
        const result = reviewers.reduce((acc, reviewer) => {
          if (reviewer.require_kpi && (!acc || reviewer.stage_id < acc.stage_id)) {
            return reviewer;
          }
          return acc;
        }, null);
        if (Number(result.stage_id) >= Number(stage)) {
          return false;
        }
        return true;
      }

      const getClass = (program, value, month, stage = null) => {
        let table = null;
        stage ? stage == 3 ? table = "MSA" : table = "HR" : table = null;
        let baseTable = [];
        switch (table) {
          case 'MSA':
            baseTable = historyDataKpiMSA.value;
            break;
          case 'HR':
            baseTable = historyDataKpiHR.value;
            break;
          default:
            baseTable = historyDataKpi.value;
        }
        if (value >= 0 && month) {
          const historyItem = baseTable.find((h) => h.program === program);
          if (historyItem) {
            const oldValue = Number(historyItem[`m${month}`]);
            const newValue = Number(value) || 0;
            if (newValue > oldValue) {
              return 'increase';
            } else if (newValue < oldValue) {
              return 'decrease';
            } else {
              return 'no-change';
            }
          } else {
            if (value > 0) {
              return 'increase';
            } else if (value < 0) {
              return 'no-change';
            }
          }
        }
      }

      onMounted(async () => {
        const url = new URL(window.location.href);
        const id = url.searchParams.get('id');
        const tab = url.searchParams.get('tab');
        let type = null;
        if (tab) {
          switch (tab) {
            case '1':
              activeName.value = 'pending';
              type = 'pending';
              break;
            case '2':
              activeName.value = 'approved';
              type = 'approved';
              break;
            case '3':
              activeName.value = 'rejected';
              type = 'rejected';
              break;
            default:
              break;
          }
        }
        if (id && tab && type) {
          searchQuery.value = id;
          handleSearch(type);
          return;
        }
        await getMsSignupList(0, arFilter = {
          status: 'pending'
        });
      });

      const submitForm = async () => {
        loading.value = true;
        loadingKPI.value = true;
        $data = {
          ...form.value,
          id: form.value.ms_list_id,
        };
        const flag = validateTableKpiData();
        if (flag !== true) {
          showNotification('warning', flag);
          loading.value = false;
          loadingKPI.value = false;
          return;
        }
        if (isEdit.value) {
          const res = await handleCreateKpi();
          if (res) {
            const result = await handleApprove($data);
            if (result === true) {
              reloadPage();
            }
          } else {
            loadingKPI.value = false;
            loading.value = false;
          }
          return;
        }
        const result = await handleApprove($data);
        if (result === true) {
          reloadPage();
        }
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
        loadingHistory,
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
        isDisabled,
        getSummaries,
        calculateRowTotal,
        checkStep,
        checkChangeKpi,
        timelineLoadingText,
        noMoreText,
        textReviewerName,
        textReviewerName2,
        btnKpisMemberText,
        showKpisMember,
        showKPI,
        hideKPIsMember,
        activeNameKpi,
        handleClickTab,
        teamMsMember,
        tableDataKpiMemberHR,
        tableDataKpiMemberMSA,
        loadingMemberKPI,
        loadingKPI,
        checkViewerMemberKpi,
        checkShowKPI,
        getClass,
        tableRef,
        checkShowDelete
      }
    }
  });

  app.use(ElementPlus);
  app.mount('#ms_processes');
</script>