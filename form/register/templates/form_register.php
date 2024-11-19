<div v-if="showForm === 1" class="manager-notice">
    <el-alert
        type="warning"
        :closable="false"
        show-icon>
        <template #title>
            Bạn đã là MS, không thể đăng ký lại. Nếu bạn muốn hủy đăng ký hoặc chuyển team, vui lòng <a href="<?php echo $config['base_url'] . '/' . $config['root_folder'] . '/form/unregister/' ?>" target='_blank'>click vào đây</a>
        </template>
    </el-alert>
</div>
<div v-if="showForm === 2" class="manager-notice">
    <el-alert
        type="info"
        :closable="false"
        show-icon>
        <template #title>
            Yêu cầu đăng ký làm MS của bạn đang được xét duyệt. Xin vui lòng đợi hoặc <a href="<?php echo $config['base_url'] . '/' . $config['root_folder'] . '/form/list/' ?>" target='_blank'>click vào đây để theo dõi.</a>
        </template>
    </el-alert>
</div>
<div v-if="showForm === 3" v-loading="pageLoading">
    <div class="title">
        <h1 style="font-size:25px;margin:10px 0;" class="title-page" v-html="pageTitle"></h1>
    </div>
    <div class="form-register">
        <el-form :model="form" :rules="rules" ref="ruleFormRef" label-width="auto" style="width: 800px; margin-top: 32px;">
            <el-form-item label="Họ và tên:" prop="user_name">
                <el-input v-model="form.user_name" :disabled="true" />
            </el-form-item>

            <el-form-item label="Email:" prop="user_email">
                <el-input v-model="form.user_email" :disabled="true" />
            </el-form-item>

            <el-form-item label="Mã nhân viên:" prop="employee_id">
                <el-input v-model="form.employee_id" :disabled="true" />
            </el-form-item>

            <el-form-item label="Phòng ban:" prop="department">
                <el-input v-model="dpmString" :disabled="true" />
            </el-form-item>

            <el-form-item label="Trưởng phòng xét duyệt:" prop="manager">
                <template v-if="selectedManager">
                    <div class="selected-manager">
                        <div class="manager-left">
                            <div class="manager-avatar">
                                <el-avatar
                                    :size="40"
                                    :src="selectedManager.avatar || ''"
                                    :alt="selectedManager.name">
                                    <span>{{ selectedManager.name.charAt(0) }}</span>
                                </el-avatar>
                            </div>
                            <div class="manager-info">
                                <div class="manager-name">{{ selectedManager.name }}</div>
                                <div class="manager-position">{{ selectedManager.position || selectedManager.type }}</div>
                            </div>
                            <div class="remove-button">
                                <i class="fas fa-times" @click="removeManager"></i>
                            </div>
                        </div>
                    </div>

                    <div class="manager-notice">
                        <el-alert
                            title="Lưu ý: Nếu không đúng trưởng phòng xét duyệt, vui lòng chọn lại"
                            type="warning"
                            :closable="false"
                            show-icon />
                    </div>
                </template>
                <template v-else>
                    <el-input
                        v-model="searchManager"
                        placeholder="Tìm kiếm trưởng phòng"
                        @input="(value) => debouncedSearch(value)">
                    </el-input>
                    <div v-if="searchResults.length" class="search-results">
                        <div
                            v-for="manager in searchResults"
                            :key="manager.id"
                            class="manager-item"
                            @click="selectManager(manager)">
                            <div class="manager-avatar">
                                <el-avatar
                                    :size="40"
                                    :src="manager.avatar || ''"
                                    :alt="manager.name">
                                    <span>{{ manager.name.charAt(0) }}</span>
                                </el-avatar>
                            </div>
                            <div class="manager-info">
                                <div class="manager-name">{{ manager.name }}</div>
                                <div class="manager-position">{{ manager.position || manager.type}}</div>
                            </div>
                        </div>
                    </div>
                </template>

            </el-form-item>

            <el-form-item label="Chọn team MS:" prop="team_ms">
                <el-select
                    v-model="form.team_ms_id"
                    placeholder="Select">
                    <el-option
                        v-for="value in listTeamMS"
                        :key="value.ID"
                        :label="value.NAME"
                        :value="value.ID">
                    </el-option>
                </el-select>
            </el-form-item>

            <el-form-item label="Chọn vai trò MS:" prop="type_ms">
                <el-select v-model="form.type_ms_id" placeholder="Select">
                    <el-option
                        v-for="(value, key) in typeMS"
                        :key="key"
                        :label="value"
                        :value="key" />
                </el-select>
            </el-form-item>

            <el-form-item label="Danh sách đề xuất:" prop="list_propose">
                <el-select
                    v-model="form.list_propose"
                    multiple
                    collapse-tags
                    collapse-tags-tooltip
                    :max-collapse-tags="3"
                    placeholder="Select">
                    <el-option
                        v-for="item in listPropose"
                        :key="item"
                        :label="item"
                        :value="item" />
                </el-select>
            </el-form-item>

            <div>
                <el-form-item @click="dialogVisible = true" :style="{ color: 'blue', cursor: 'pointer' }">
                    <div v-html="dialogLink"></div>
                </el-form-item>

                <el-dialog
                    v-model="dialogVisible"
                    title="Quy định về bảo mật thông tin và cam kết"
                    width="700"
                    height="500">
                    <div v-html="dialogContent"></div>

                    <el-form-item prop="checkbox" style="margin-top: 16px">
                        <el-checkbox
                            width="500"
                            v-model="form.confirmation"
                            true-label="Tôi ĐỒNG Ý và CAM KẾT tuân thủ các quy định của công ty"
                            false-label="">
                            <div v-html="dialogCheckbox"></div>
                        </el-checkbox>
                    </el-form-item>

                    <template #footer>
                        <div class="dialog-footer">
                            <el-button @click="dialogVisible = false">Cancel</el-button>
                            <el-button
                                type="primary"
                                @click="submitForm"
                                :loading="loading"
                                :disabled="loading || !isFormValid">
                                Gửi
                            </el-button>
                        </div>
                    </template>
                </el-dialog>
            </div>
        </el-form>
    </div>
</div>