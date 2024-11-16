<div v-if="showForm === 1" class="manager-notice">
    <el-alert
        type="warning"
        :closable="false"
        show-icon>
        <template #title>
            Bạn đã là MS, không thể đăng ký lại. Nếu bạn muốn hủy đăng ký hoặc chuyển team, vui lòng <a href='https://bitrixdev.esuhai.org/ms-signup/form/unregister/' target='_blank'>click vào đây</a>
        </template>
    </el-alert>
</div>
<div v-if="showForm === 2" class="manager-notice">
    <el-alert
        type="info"
        :closable="false"
        show-icon>
        <template #title>
            Yêu cầu đăng ký làm MS của bạn đang được xét duyệt. Xin vui lòng đợi hoặc <a href='https://bitrixdev.esuhai.org/ms-signup/form/list/' target='_blank'>click vào đây để theo dõi.</a>
        </template>
    </el-alert>
</div>
<div v-if="showForm === 3">
    <!-- <div id="loader">
        <span span class="loader"></span>
    </div> -->
    <div class="title">
        <h1 style="font-size:25px;margin:10px 0;" class="title-page">Form đăng ký làm MS</h1>
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
                    Xem và chấp nhận quy định về việc tham gia hoạt động MS
                </el-form-item>

                <el-dialog
                    v-model="dialogVisible"
                    title="Quy định về bảo mật thông tin và cam kết"
                    width="700"
                    height="500"
                >
                    <div class="dialog-content">
                        <div class="main-commitments">
                            <p>1. <span>TRUNG THỰC</span> trong giao dịch, đảm bảo tính chính xác của bảng chào giá của công ty Esuhai Group. Nếu có thương lượng thay đổi giá, phải thông qua phê duyệt của Công ty.</p>
                            <p>2. <span>KHÔNG</span> tiến hành thu thập thông tin liên quan S2Group nếu không được phép tiếp cận;</p>
                            <p>3. <span>TUYỆT ĐỐI KHÔNG</span> lạm dụng chức vụ, quyền hạn, nhiệm vụ để móc nối môi giới, cò mồi người lao động, các đối tác để nhận tiền, quà, hiện vật, … dưới mọi hình thức, thời gian, địa điểm, ….</p>
                            <p>4. <span>TUYỆT ĐỐI KHÔNG</span> cung cấp thông tin, dữ liệu liên quan hồ sơ, công việc, tình hình Công ty, khách hàng, đối tác, dự án đang triển khai, bí mật kinh doanh…cho bên thứ ba và/hoặc cơ quan ngôn luận dưới mọi hình thức mà chưa được sự chấp thuận của Ban Giám đốc;</p>
                            <p>5. <span>KHÔNG ĐƯỢC PHÉP</span> trực tiếp hay gián tiếp tiết lộ hoặc để cho bất kỳ cá nhân hay tổ chức nào khác (kể cả người trong S2Group nếu người đó không được quyền tiếp cận thông tin bảo mật) sử dụng trừ khi điều đó là yêu cầu của công việc và/hoặc có sự đồng ý của cấp trên;</p>
                        </div>

                        <div class="working-period">
                            <p>6. <span>Trong thời gian làm việc tại Công ty Esuhai Group</span> tôi cam kết:</p>
                            <ul>
                                <li>Không đồng thời làm việc hay cộng tác dưới bất cứ hình thức nào với tổ chức, cá nhân có quyền lợi đối lập hoặc có khả năng cạnh tranh với Công ty</li>
                                <li>Không lợi dụng quan hệ giữa Công ty và khách hàng, đối tác của S2Group để thiết lập quan hệ giao dịch với Khách hàng, đối tác vì mục đích cá nhân hoặc vì bất cứ mục đích nào khác mà không được sự chấp thuận của Ban Giám đốc.</li>
                                <li>Đồng ý cho Công ty Group được sử dụng thông tin, hình ảnh của mình phục vụ cho mục đích truyền thông cho các hoạt động của Công ty</li>
                                <li>Thực hiện quy định về việc sử dụng Facebook cá nhân trong công việc nhằm nâng cao thương hiệu cá nhân của nhân sự Esuhai đồng thời bảo vệ quyền lợi, hình ảnh, thương hiệu, uy tín của Công ty.</li>
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

                    <el-form-item prop="checkbox" style="margin-top: 16px">
                        <el-checkbox
                            width="500"
                            v-model="form.confirmation"
                            true-label="Tôi ĐỒNG Ý và CAM KẾT tuân thủ các quy định của công ty"
                            false-label="">
                            Tôi đã đọc và ĐỒNG Ý, CAM KẾT tuân thủ các quy định của công ty
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
