<script>
    const {
        createApp,
        ref,
        onMounted,
        computed
    } = Vue;

    const app = createApp({
        setup() {
            const ruleFormRef = ref(null);
            const listTeamMS = ref({});
            const selectedManager = ref(null);
            const searchManager = ref('');
            const searchResults = ref([]);
            const manager = ref(null);
            const loading = ref(false);
            const pageLoading = ref(true);
            const showForm = ref(0);
            const dialogVisible = ref(false);
            const userName = <?= json_encode($userFullName ?? "") ?>;
            const userEmail = <?= json_encode($userEmail ?? "") ?>;
            const userId = <?= json_encode($userID ?? "") ?>;
            const msId = <?= json_encode($msId ?? "") ?>;
            const typeMS = <?= json_encode($typeMS ?? "") ?>;
            const employeeId = <?= json_encode($employeeId ?? null) ?>;
            const dpmString = <?= json_encode($departmentLabels ?? null) ?>.join(', ');
            const departmentId = <?= json_encode($departmentId ?? null) ?>;
            const MSAid = <?= json_encode($config['msa_id'] ?? null) ?>;
            const form = ref({
                user_id: Number(userId),
                user_name: userName,
                user_email: userEmail,
                employee_id: 1,
                manager: null,
                team_ms_id: null,
                type_ms_id: null,
                list_propose: null,
                status: 'pending',
                department: dpmString,
                department_id: departmentId,
                confirmation: false,
                msl_id: null,
                msa_id: null,
            });

            const listPropose = ref([
                "Quyền truy cập CRM (DCC)",
                "Gia nhập nhóm email MS (HR)",
                "Thêm vào nhóm và phòng ban MS (HR)",
                "Line tổng đài Omical (ICT)",
                "Tai nghe",
                "Webcam",
                "SIM Zalo (có 4G)"
            ])

            const pageTitle = `Form đăng ký tham gia hoạt động MS`;
            const dialogContent = `
                <div class="dialog-content">
                    <div class="main-commitments">
                        <p>1. <span>TRUNG THỰC</span> trong giao dịch, đảm bảo tính chính xác của bảng chào giá của công ty Esuhai Group. Nếu có thương lượng thay đổi giá, phải thông qua phê duyệt của Công ty.</p>
                        <p>2. <span>KHÔNG</span> tiến hành thu thập thông tin liên quan S2Group nếu không được phép tiếp cận;</p>
                        <p>3. <span>TUYỆT ĐỐI KHÔNG</span> lạm dụng chức vụ, quyền hạn, nhiệm vụ để móc nối môi giới, cò mồi người lao động, các đối tác để nhận tiền, quà, hiện vật, … dưới mọi hình thức, thời gian, địa điểm, ….</p>
                        <p>4. <span>TUYỆT ĐỐI KHÔNG</span> cung cấp thông tin, dữ liệu liên quan hồ sơ, công việc, tình hình Công ty, khách hàng, đối tác, dự án đang triển khai, bí mật kinh doanh…cho bên thứ ba và/hoặc cơ quan ngôn luận dưới mọi hình thức mà chưa được sự chấp thuận của Ban Giám đốc;</p>
                        <p>5. <span>KHÔNG ĐƯỢC PHÉP</span> trực tiếp hay gián tiếp tiết lộ hoặc để cho bất kỳ cá nhân hay tổ chức nào khác (kể cả người trong S2Group nếu người đó không được quyền tiếp cận thông tin bảo mật) sử dụng trừ khi điều đó là yêu cầu của công việc và/hoặc có sự đồng ý của cấp trên;</p>
                    </div>
            
                    <div class="working-period">
                        <p>6. <span>Trong thởi gian làm việc tại Công ty Esuhai Group</span> tôi cam kết:</p>
                        <ul>
                            <li>Không đồng thởi làm việc hay cộng tác dưới bất cứ hình thức nào với tổ chức, cá nhân có quyền lợi đối lập hoặc có khả năng cạnh tranh với Công ty</li>
                            <li>Không lợi dụng quan hệ giữa Công ty và khách hàng, đối tác của S2Group để thiết lập quan hệ giao dịch với Khách hàng, đối tác vì mục đích cá nhân hoặc vì bất cứ mục đích nào khác mà không được sự chấp thuận của Ban Giám đốc.</li>
                            <li>Đồng ý cho Công ty Group được sử dụng thông tin, hình ảnh của mình phục vụ cho mục đích truyền thông cho các hoạt động của Công ty</li>
                            <li>Thực hiện quy định về việc sử dụng Facebook cá nhân trong công việc nhằm nâng cao thương hiệu cá nhân của nhân sự Esuhai đồng thởi bảo vệ quyền lợi, hình ảnh, thương hiệu, uy tín của Công ty.</li>
                            <li>Không tự mình hoặc kết hợp hoặc thay mặt bất cứ cá nhân hoặc tổ chức nào tiến hành bất kỳ hoạt động kinh doanh nào cạnh tranh trực tiếp hoặc gián tiếp với Công ty.</li>
                        </ul>
                    </div>
            
                    <div class="responsibility">
                        <p><span>TÔI CAM ĐOAN CÓ TRÁCH NHIỆM BỒI THƯỜNG MỌI THIỆT HẠI CHO CÔNG TY</span>, bao gồm không giới hạn những tổn thất về vật chất, uy tín, hình ảnh, chi phí để khắc phục thiệt hại, chi phí kiện tụng, luật sư.</p>
                        <p>Tôi hiểu rằng Công ty Esuhai Group hoàn toàn có thể thực hiện một hoặc đồng thời các biện pháp sau đây:</p>
                        <p>1. Yêu cầu tôi bồi thường thiệt hại do những tổn thất mà S2Group phải gánh chịu do hậu quả của việc tiết lộ thông tin bảo mật của tôi gây ra;</p>
                        <p>2. Khởi kiện tại Tòa án có thẩm quyền theo quy định của pháp luật hiện hành.</p>
                    </div>
                </div>
            `;
            const dialogCheckbox = ` Tôi đã đọc và ĐỒNG Ý, CAM KẾT tuân thủ các quy định của công ty`;
            const dialogLink = `Xem và chấp nhận quy định về việc tham gia hoạt động MS`;

            const rules = {
                team_ms_id: [{
                    required: true,
                    message: 'Vui lòng chọn Team MS',
                    trigger: 'change'
                }],
                type_ms_id: [{
                    required: true,
                    message: 'Vui lòng chọn vai trò MS',
                    trigger: 'change'
                }],
                manager: [{
                    required: true,
                    message: 'Vui lòng chọn Trưởng phòng xét duyệt',
                    trigger: 'change'
                }],
                confirmation: [{
                    validator: (rule, value, callback) =>
                        value === false ? callback(new Error('Vui lòng đồng ý tham gia hoạt động MS và cam kết hoàn thành mục tiêu')) : callback(),
                    trigger: 'change'
                }]
            };

            const selectedTeamName = computed(() => {
                const selectedTeam = listTeamMS.value.find(team => team.ID === form.value.team_ms_id);
                return selectedTeam ? selectedTeam.NAME : '';
            });

            const removeManager = () => {
                selectedManager.value = null;
                form.value.manager = '';
                ruleFormRef.value.clearValidate('manager');
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

            const handleSearchManager = async (name) => {
                if (!name) {
                    searchResults.value = [];
                    return;
                }
                try {
                    const response = await axios.get(`../../api/search_manager.php`, {
                        params: {
                            name: name
                        }
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

            const selectManager = (manager) => {
                selectedManager.value = {
                    id: manager.id,
                    name: manager.name,
                    avatar: manager.avatar || '',
                    department: manager.department,
                    position: manager.position || '',
                    work_position: manager.work_position || '',
                    type: manager.type || ""
                };
                form.value.manager = manager.id;
                searchResults.value = [];
                searchManager.value = '';
                ruleFormRef.value.clearValidate('manager');
            };

            const debouncedSearch = debounce((query) => {
                handleSearchManager(query);
            }, 500);

            async function getListTeamMS() {
                try {
                    const response = await axios.get(`../../api/get_list_team_ms.php`);
                    const data = response.data;

                    if (data.success && data.data) {
                        listTeamMS.value = Object.entries(data.data).map(([id, dept]) => ({
                            ID: dept.ID,
                            NAME: dept.NAME
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

            async function getHeadDepartment(id, type) {
                try {
                    const response = await axios.get('../../api/get_head_department.php', {
                        params: {
                            id: id,
                            type: type // 'userId' hoặc 'departmentId'
                        },
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    if (response.data.success) {
                        return response.data.data;
                    } else {
                        throw new Error(response.data.message || 'Unknown error');
                    }
                } catch (error) {
                    console.error(`Error fetching ${type === 'userId' ? 'direct manager' : 'department head'}:`, error);
                    throw error;
                }
            }

            const isFormValid = computed(() => {
                return form.value.confirmation;
            });

            async function getFormSubmited(userId) {
                try {
                    const response = await axios.get('../../api/get_form_submited.php', {
                        params: {
                            user_id: userId
                        }
                    });
                    if (response.data && response.data.success === true) {
                        return response.data.data;
                    } else {
                        throw new Error('No data received from server');
                    }
                } catch (error) {
                    console.error('Error fetching submitted forms:', error);
                    throw error;
                }
            }

            onMounted(async () => {
                if (msId) {
                    showForm.value = 1;
                    return;
                }
                const response = await getFormSubmited(userId);

                if (response.items[0] && response.items[0].status === "pending") {
                    showForm.value = 2;
                    return;
                }
                showForm.value = 3;
                await getListTeamMS();
                try {
                    manager.value = await getHeadDepartment(userId, 'userId');
                    if (manager) {
                        await handleSearchManager(manager.value.fullName);

                        if (searchResults.value.length > 0) {
                            selectManager(searchResults.value[0]);
                        }
                    }
                } catch (error) {
                    console.error('Error in onMounted:', error);
                }
                pageLoading.value = false;
            });

            const showNotification = (type, message) => {
                ElementPlus.ElNotification({
                    type: type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'error',
                    message: message,
                    duration: 2000,
                    position: 'top-right'
                });
            };

            const submitForm = async () => {
                ruleFormRef.value.validate(async (valid, fields) => {
                    if (!valid) {
                        dialogVisible.value = false;
                        return;
                    }
                    try {
                        console.log(form.value);
                        loading.value = true;
                        const headMS = await getHeadDepartment(form.value.team_ms_id, 'departmentId');
                        const headMSA = await getHeadDepartment(MSAid, 'departmentId');
                        form.value.msl_id = headMS.id;
                        form.value.msa_id = headMSA.id;
                        form.value.type_ms = typeMS[form.value.type_ms_id];
                        listTeamMS.value.forEach(element => {
                            if (element.ID === form.value.team_ms_id) {
                                form.value.team_ms = element.NAME;
                                return;
                            }
                        });
                        form.value.department_id = JSON.stringify(form.value.department_id);
                        if (form.value.list_propose != null) {
                            form.value.list_propose = JSON.stringify(form.value.list_propose);
                        }
                        //const response = await axios.post(`./register_wf.php`, formData);

                        const response = await axios.post(`../../api/create_ms_register.php`, form.value);
                        const data = response.data;
                        if (data.success) {
                            showNotification('success', 'Gửi đơn đăng ký thành công.');
                            setTimeout(() => {
                                window.location.href = "<?php echo $config['base_url']; ?>/<?php echo $config['root_folder']; ?>/form/list/";
                            }, 1000);
                        } else {
                            loading.value = false;
                            showNotification('error', data.message || 'Có lỗi xảy ra!');
                        }
                    } catch (error) {
                        loading.value = false;
                        showNotification('error', error.message || 'Có lỗi xảy ra!');
                    }

                })
            };

            return {
                form,
                rules,
                loading,
                pageLoading,
                submitForm,
                ruleFormRef,
                listTeamMS,
                selectedManager,
                searchManager,
                searchResults,
                removeManager,
                handleSearchManager,
                selectManager,
                debouncedSearch,
                isFormValid,
                showForm,
                typeMS,
                userId,
                dpmString,
                listPropose,
                dialogVisible,
                dialogContent,
                dialogCheckbox,
                dialogLink,
                pageTitle
            }
        }
    });

    app.use(ElementPlus);
    app.mount('#form-register');
</script>