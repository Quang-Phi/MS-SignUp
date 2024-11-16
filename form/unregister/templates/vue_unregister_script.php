<script>
    const {
        createApp,
        ref,
        onMounted,
        computed
    } = Vue;

    const app = createApp({
        setup() {
            const listTeamMS = ref({});
            const ruleFormRef = ref(null);
            const loading = ref(false);
            const flag = ref(false);
            const userName = <?= json_encode($userFullName ?? "") ?>;
            const userId = <?= json_encode($userID ?? "") ?>;
            const msId = <?= json_encode($msId ?? "") ?>;
            const typeMSLabel = <?= json_encode($typeMSLabel ?? "") ?>;
            const dpm = <?= json_encode($departmentLabels ?? []) ?>;

            const form = ref({
                name: userName,
                curr_team_ms: '',
                date_register: '',
                type_ms: '',
                team_transfer: false,
                team_ms: '',
                reason: '',
                checkbox: false
            });

            const rules = {
                team_ms: [{
                    required: !form.team_transfer,
                    message: 'Vui lòng chọn Team MS',
                    trigger: 'change'
                }],
                reason: [{
                    required: true,
                    message: 'Vui lòng nhập lý do',
                    trigger: ['change', 'blur', 'submit']
                }],
                checkbox: [{
                    validator: (rule, value, callback) =>
                        value === false ? callback(new Error('Vui lòng đồng ý tham gia hoạt động MS và cam kết hoàn thành mục tiêu')) : callback(),
                    trigger: 'change'
                }]
            };

            async function getFormSubmited(userId) {
                try {
                    const response = await axios.get('../../api/get_form_submited.php');
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
                    console.error('Error fetching list MS:', error);
                    listTeamMS.value = {};
                }
            }

            const isFormValid = computed(() => {
                return form.value.checkbox;
            });

            const filterDpmToGetTeamMS = computed(() => {
                return dpm.filter(dept => dept.startsWith('MS'));
            });

            const handleTransferTeamChange = async () => {
                console.log(form.value.transfer_team);
                if (form.value.transfer_team && !flag.value) {
                    await getListTeamMS();
                    flag.value = true;
                }
            };

            onMounted(async () => {
                if (msId) {
                    form.value.curr_team_ms = filterDpmToGetTeamMS.value[0]
                    form.value.type_ms = typeMSLabel

                    const submittedForms = await getFormSubmited();
                    console.log(submittedForms);
                    if (submittedForms && submittedForms.PROPERTIES) {
                        if (submittedForms.PROPERTIES.TRANG_THAI_C_MS === "Hoàn thành") {
                            form.value.date_register = submittedForms.DATE_CREATE
                        }
                    }
                }

            });

            const submitForm = async () => {
                ruleFormRef.value.validate(async (valid, fields) => {
                    if (valid) {

                    }
                })
            };

            return {
                msId,
                form,
                rules,
                listTeamMS,
                isFormValid,
                submitForm,
                ruleFormRef,
                loading,
                handleTransferTeamChange
            }
        }
    });

    app.use(ElementPlus);
    app.mount('#form-unregister');
</script>