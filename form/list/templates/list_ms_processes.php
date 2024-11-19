<div class="title">
    <h1 style="font-size:25px;margin:10px 0;" class="title-page" v-html="pageTitle"></h1>
</div>
<div :class="showFormKPI ? 'overlay active' : 'overlay'" @click="hideOverlay()"></div>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <span v-html="totalText"></span>

    <el-input v-model="searchQuery" placeholder="Tìm kiếm..." style="width: 300px;" clearable @input="debouncedSearch" />
</div>
<div class="list-ms-processes">
    <el-table
        v-loading="tableLoading"
        :data="tableData"
        max-height="500"
        style="width: 100%"
        border default-expand-all>
        <el-table-column prop="id" label="ID" min-width="70"></el-table-column>
        <el-table-column prop="user_name" label="Họ và tên" min-width="150">
            <template #default="scope">
                <a :href="`${urlUserInfo}/${scope.row.user_id}/`" target="_blank">
                    {{ scope.row.user_name }}
                </a>
            </template>
        </el-table-column>
        <el-table-column prop="stage_id" label="Trạng thái" min-width="200">
            <template #default="scope">
                <el-steps
                    :active="scope.row.status === 'error' ? scope.row.stage_id : scope.row.stage_id === scope.row.max_stage && scope.row.status === 'success' ? scope.row.stage_id : scope.row.stage_id - 1"
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
        <el-table-column prop="created_at" label="Ngày đăng ký" min-width="200"></el-table-column>
        <el-table-column prop="user_email" label="Email" min-width="150"></el-table-column>
        <el-table-column prop="employee_id" label="Mã nhân viên" min-width="140"></el-table-column>
        <el-table-column prop="department" label="Phòng ban" min-width="130"></el-table-column>
        <el-table-column prop="team_ms" label="Team MS" min-width="150"></el-table-column>
        <el-table-column prop="list_propose" label="Danh sách đề xuất" min-width="300"></el-table-column>
        <el-table-column prop="confirmation" label="Xác nhận và cam kết" min-width="300"></el-table-column>
        <el-table-column prop="comments" label="Ghi chú" min-width="100"></el-table-column>
        <el-table-column fixed="right" label="Hành động" min-width="180">
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
                                    type="success"
                                    size="small"
                                    :loading="approveLoading || loading"
                                    :disabled="approveLoading"
                                    @click="handleApprove(scope.row)">
                                    Xét duyệt
                                </el-button>
                            </template>
                        </template>

                        <template v-if="!approveLoading">
                            <el-button
                                type="danger"
                                size="small"
                                :loading="rejectLoading || loading"
                                :disabled="rejectLoading"
                                @click="handleReject(scope.row)">
                                Từ chối
                            </el-button>
                        </template>

                    </template>
                    <template v-else>
                        <el-button
                            link
                            type="primary"
                            size="small"
                            :disabled="true">
                            Đang chờ xét duyệt
                        </el-button>
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

    <div style="margin-top: 20px; position:relative">
        <el-pagination
            background
            small
            layout="prev, pager, next, sizes"
            :total="total"
            :page-size="pageSize"
            :current-page="currentPage"
            :page-sizes="[10, 20, 50, 100]"
            @current-change="handlePageChange"
            @size-change="handleSizeChange">
        </el-pagination>
    </div>
</div>

<div :class="showFormKPI ? 'form-kpi active' : 'form-kpi'">
    <div class="side-panel-labels">
        <div class="side-panel-label" style="max-width: 215px;">
            <div class="side-panel-label-icon-box" title="Close" @click="hideOverlay()">
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
                    <el-select v-model="form.proposer_id" placeholder="Select">
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
                    <span v-html="linkProposerText"></span>
                </a>
            </el-form-item>
            <el-form-item label="Nhóm MS:" prop="team_ms">
                <a :href="`${urlTeamMSInfo}${form.team_ms_id}/`" target="_blank">
                    <span v-html="linkTeamMSText"></span>
                </a>
            </el-form-item>
        </div>

        <div class="form-table">
            <el-table :data="tableDataKpi" border style="width: 100%" max-height="500">
                <el-table-column fixed prop="program" label="Chương trình" min-width="140"></el-table-column>
                <template v-for="month in 12" :key="month">
                    <el-table-column :prop="'month' + month" :label="'M' + month" min-width="70">
                        <template #default="scope">
                            <el-input
                                :disabled="flag || month <= currMonth"
                                type="number"
                                size="small"
                                v-model="scope.row['m' + month]"
                                @input="(event) => handleInputChange(scope.$index, month, event)"
                                placeholder="0"
                                :min="1">
                            </el-input>
                        </template>
                    </el-table-column>
                </template>
                <el-table-column
                    v-if="!flag"
                    fixed="right"
                    label="Operations"
                    min-width="100">
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

        <div class="user_confirm" v-if="form.max_stage === form.stage_id">
            <div style="margin-top: 20px;">
                <el-checkbox v-model="form.agree_kpi">
                    <span v-html="agreeKpiText"></span>
                </el-checkbox>
            </div>
            <div v-if="form.list_propose && form.list_propose.length > 0 && form.list_propose[0].trim() !== ''">
                <el-checkbox v-model="form.received_all">
                    <span v-html="agreeReceivedText"></span>
                </el-checkbox>
                <span v-html="listText"></span>
            </div>
        </div>

        <div v-if="flag" class="form-btn">
            <el-form-item>
                <el-button
                    type="primary"
                    @click="finalSubmit()"
                    :loading="loading"
                    :disabled="loading || !isFormValid">
                    <span v-html="textBtn1"></span>
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
                    <span v-html="textBtn2"></span>
                </el-button>
            </el-form-item>

            <el-form-item v-else>
                <el-button
                    type="primary"
                    @click="submitForm('create kpi')"
                    :loading="loading"
                    :disabled="loading || approveLoading">
                    <span v-html="textBtn3"></span>
                </el-button>
                <el-button
                    type="primary"
                    @click="submitForm('create and approve')"
                    :loading="approveLoading"
                    :disabled="approveLoading || loading">
                    <span v-html="textBtn4"></span>
                </el-button>
            </el-form-item>
        </div>
    </el-form>
</div>