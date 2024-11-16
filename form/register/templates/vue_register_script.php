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
                "Quyền truy cập CRM",
                "Gia nhập nhóm email MS",
                "Thêm vào danh sách MS",
                "Line tổng đài Omical",
                "Thiết bị MS (Tai nge, webcam,...)",
            ])

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
            });

            const submitForm = async () => {
                ruleFormRef.value.validate(async (valid, fields) => {
                    if (!valid) {
                        dialogVisible.value = false;
                        return;
                    }
                    try {
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
                        form.value.list_propose = JSON.stringify(form.value.list_propose);
                        console.log(form.value);
                        //const response = await axios.post(`./register_wf.php`, formData);

                        const response = await axios.post(`../../api/create_ms_regiser.php`, form.value);
                        const data = response.data;
                        if (data.success) {
                            window.location.href = "https://bitrixdev.esuhai.org/ms-signup/form/list/";
                        } else {
                            loading.value = false;
                            ElementPlus.ElNotification({
                                message: data.message || 'Có lỗi xảy ra!',
                                type: 'error',
                                duration: 2000,
                                position: 'top-right'
                            });
                        }
                    } catch (error) {
                        loading.value = false;
                        ElementPlus.ElNotification({
                            message: data.message || 'Có lỗi xảy ra!',
                            type: 'error',
                            duration: 2000,
                            position: 'top-right'
                        });
                    }

                })
            };

            return {
                form,
                rules,
                loading,
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
                dialogVisible
            }
        }
    });

    app.use(ElementPlus);
    app.mount('#form-register');
</script>