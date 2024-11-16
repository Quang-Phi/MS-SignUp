    <div class="title">
        <h1 style="font-size:25px;margin:10px 0;" class="title-page">Danh sách chờ xét duyệt</h1>
    </div>
    <div :class="showFormKPI ? 'overlay active' : 'overlay'" @click="showFormKPI = false"></div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <span>TỔNG:</span>
            <span style="font-weight: bold; margin-left: 4px">{{ total }}</span>
        </div>

        <el-input v-model="searchQuery" placeholder="Tìm kiếm..." style="width: 300px;" clearable @input="handleSearch"/>
    </div>
    <div class="list-ms-processes">
        <el-table
            :data="tableData"
            :default-sort="{prop: 'id', order: 'descending'}"
            style="width: 100%"
            border default-expand-all>
            <el-table-column prop="id" sortable label="ID" min-width="70"></el-table-column>
            <el-table-column prop="user_name" sortable label="Họ và tên" min-width="150">
                <template #default="scope">
                    <a :href="`${urlUserInfo}/${scope.row.user_id}/`" target="_blank">
                        {{ scope.row.user_name }}
                    </a>
                </template>
            </el-table-column>
            <el-table-column prop="stage_id" sortable label="Trạng thái" min-width="200">
                <template #default="scope">
                    <el-steps
                        :active="scope.row.status === 'error' ? scope.row.stage_id : scope.row.stage_id - 1"
                        :finish-status="scope.row.status == 'pending' ? 'success' : scope.row.status"
                        :class="scope.row.status == 'pending' ? 'success': scope.row.status"
                        simple>
                        <template v-for="n in parseInt(scope.row.max_stage)" :key="n">
                            <el-tooltip
                                class="item"
                                effect="dark"
                                :content="scope.row.reviewers.find(r => r.stage_id === n)?.stage_label || `Step ${n}`"
                                placement="top">
                                <el-step :title="scope.row.reviewers.find(r => r.stage_id === n)?.stage_label || `Step ${n}`"></el-step>
                            </el-tooltip>
                        </template>
                    </el-steps>
                </template>
            </el-table-column>
            <el-table-column prop="created_at" sortable label="Ngày đăng ký" min-width="200"></el-table-column>
            <el-table-column prop="user_email" sortable label="Email" min-width="150"></el-table-column>
            <el-table-column prop="employee_id" sortable label="Mã nhân viên" min-width="140"></el-table-column>
            <el-table-column prop="department" label="Phòng ban" min-width="130"></el-table-column>
            <el-table-column prop="team_ms" sortable label="Team MS" min-width="150"></el-table-column>
            <el-table-column prop="list_propose" label="Danh sách đề xuất" min-width="300"></el-table-column>
            <el-table-column prop="confirmation" label="Xác nhận và cam kết" min-width="300"></el-table-column>
            <el-table-column fixed="right" label="Hành động" min-width="150">
                <template #default="scope">
                    <div v-if="scope.row.status === 'pending'">

                        <template v-if="scope.row.reviewers.some(reviewer =>
                                        reviewer.reviewer_id === parseInt(userId) &&
                                        reviewer.stage_id === parseInt(scope.row.stage_id))">
                            <template v-if="scope.row.reviewers.find(r =>
                                            r.stage_id === parseInt(scope.row.stage_id))?.require_kpi">
                                <template v-if="parseInt(scope.row.user_id) === parseInt(userId) &&
                                                                    parseInt(scope.row.stage_id) === parseInt(scope.row.max_stage)">
                                    <template v-if="!rejectLoading">
                                        <el-button
                                            link
                                            type="primary"
                                            size="small"
                                            @click="handleAddKPI(scope.row)">
                                            Xem KPI
                                        </el-button>
                                    </template>
                                </template>

                                <template v-else>
                                    <template v-if="!rejectLoading">
                                        <el-button
                                            link
                                            type="primary"
                                            size="small"
                                            @click="handleAddKPI(scope.row)">
                                            {{ scope.row.reviewers.find(r =>
                                            r.stage_id === parseInt(scope.row.stage_id))?.has_kpi? 'Xem KPI' : 'Nhập KPI' }}
                                        </el-button>
                                    </template>
                                </template>
                            </template>

                            <template v-else>
                                <template v-if="!rejectLoading">
                                    <el-button
                                        link
                                        type="success"
                                        size="small"
                                        :loading="approveLoading"
                                        :disabled="approveLoading"
                                        @click="handleApprove(scope.row)">
                                        Xét duyệt
                                    </el-button>
                                </template>
                            </template>

                            <template v-if="!approveLoading">
                                <el-button
                                    link
                                    type="danger"
                                    size="small"
                                    :loading="rejectLoading"
                                    :disabled="rejectLoading"
                                    @click="handleReject(scope.row)">
                                    Từ chối
                                </el-button>
                            </template>

                        </template>
                        <template v-else>
                            <span style="color: #999">Không có quyền thao tác</span>
                        </template>
                    </div>
                    <div v-else>
                        <el-button
                            link
                            :type="scope.row.status === 'success' ? 'success' : 'danger'"
                            size="small"
                            disabled>
                            {{ scope.row.status === 'success' ? 'Đã duyệt' : 'Đã từ chối' }}
                        </el-button>
                    </div>
                </template>
            </el-table-column>
        </el-table>

        <div style="margin-top: 20px; display: flex; align-items: center; justify-content: space-between;">
            <el-pagination background small layout="prev, pager, next" :total="total" :page-size="pageSize"
                :current-page="currentPage" @current-change="handlePageChange">
            </el-pagination>
            <p>{{ pageSize }} items / 1 page</p>
        </div>
    </div>

    <div :class="showFormKPI ? 'form-kpi active' : 'form-kpi'">
        <div class="side-panel-labels">
            <div class="side-panel-label" style="max-width: 215px;">
                <div class="side-panel-label-icon-box" title="Close" @click="showFormKPI = false">
                    <div class="side-panel-label-icon side-panel-label-icon-close"></div>
                </div><span class="side-panel-label-text"></span>
            </div>
        </div>
        <el-form :model="form" :rules="rules" ref="ruleFormRef" label-width="auto" style="margin-top: 32px;">
            <div class="form-control">
                <el-form-item label="Người đề nghị KPI:" prop="stage">
                    <template v-if="form.stage_id != form.max_stage">
                        <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == form.stage_id).reviewer_id}/`" target="_blank">
                            {{ form.stage }}
                        </a>
                    </template>
                    <template v-else>
                        <el-select v-model="form.proposer" placeholder="Select">
                            <el-option
                                v-for="proposer in listProposer"
                                :key="proposer.stage_id"
                                :label="proposer.label"
                                :value="proposer.stage_id"
                                @click="getTableDataKpi(form.ms_list_id, form.user_id, proposer.stage_id)" />
                        </el-select>
                    </template>
                </el-form-item>
                <el-form-item label="Người nhận KPI:" prop="user_name">
                    <a :href="`${urlUserInfo}/${form.user_id}/`" target="_blank">
                        {{ form.user_name }}
                    </a>
                </el-form-item>
                <el-form-item label="Nhóm MS:" prop="team_ms">
                    <a :href="`${urlTeamMSInfo}${form.team_ms_id}/`" target="_blank">
                        {{ form.team_ms }}
                    </a>
                </el-form-item>
            </div>

            <div class="form-table">
                <el-table :data="tableDataKpi" border style="width: 100%" max-height="250">
                    <el-table-column fixed prop="program" label="Chương trình" width="140"></el-table-column>
                    <template v-for="month in 12" :key="month">
                        <el-table-column :prop="'month' + month" :label="'M' + month" width="80">
                            <template #default="scope">
                                <el-input
                                    :disabled="flag || month <= currMonth"
                                    type="number"
                                    size="small"
                                    v-model="scope.row['m' + month]"
                                    @input="(event) => handleInputChange(scope.$index, month, event)"
                                    placeholder="0">
                                </el-input>
                            </template>
                        </el-table-column>
                    </template>
                    <el-table-column v-if="!flag" fixed="right" label="Operations" min-width="100">
                        <template #default="scope">
                            <el-button
                                link
                                type="primary"
                                size="small"
                                @click.prevent="deleteRow(scope.$index, scope.row.program)">
                                Remove
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-select v-if="!flag" class="mt-4" style="width: 100%" placeholder="Chọn chương trình">
                    <el-option
                        v-for="program in listProgram"
                        @click="onAddItem(program)"
                        :key="program"
                        :label="program"
                        :value="program">
                    </el-option>
                </el-select>
            </div>

            <div class="user_confirm">
                <div style="margin: 20px 0;">
                    <el-checkbox v-model="form.agree_kpi">
                        Tôi đồng ý với các KPI được phân công ở bảng trên
                    </el-checkbox>
                </div>

                <div style="margin: 20px 0;">
                    <el-checkbox v-model="form.received_all">
                        Tôi đã nhận đủ các phần yêu cầu sau:
                    </el-checkbox>

                    <div v-if="form.list_propose" style="margin-left: 24px; margin-top: 10px;">
                        <ul>
                            <li v-for="(item, index) in form.list_propose" :key="index" style="margin: 5px 0;">
                                {{ item.trim() }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div v-if="flag" class="form-btn">
                <el-form-item>
                    <el-button
                        type="primary"
                        @click=""
                        :loading="loading"
                        :disabled="loading">
                        Xác nhận
                    </el-button>
                </el-form-item>
            </div>
            <div v-else class="form-btn">
                <el-form-item v-if="form.has_kpi">
                    <el-button
                        type="primary"
                        @click="submitForm('approve')"
                        :loading="approveLoading"
                        :disabled="approveLoading">
                        Đồng ý duyệt
                    </el-button>
                </el-form-item>

                <el-form-item v-else>
                    <el-button
                        type="primary"
                        @click="submitForm('create kpi')"
                        :loading="loading"
                        :disabled="loading || approveLoading">
                        Gửi KPI
                    </el-button>
                    <el-button
                        type="primary"
                        @click="submitForm('create and approve')"
                        :loading="approveLoading"
                        :disabled="approveLoading || loading">
                        Gửi và Duyệt
                    </el-button>
                </el-form-item>
            </div>
        </el-form>
    </div>
