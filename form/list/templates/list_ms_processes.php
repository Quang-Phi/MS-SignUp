<div class="title">
    <h1 style="font-size:25px;margin:10px 0;" class="title-page" v-html="pageTitle"></h1>
</div>
<div :class="showFormKPI ? 'overlay active' : 'overlay'" @click="hideOverlay()"></div>
<div class="list-ms-processes">
    <el-tabs v-model="activeName" @tab-click="handleClick">
        <el-tab-pane label="Chờ xét duyệt" name="pending" class="tab-pane-pending">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span v-html="totalText"></span>
                <el-input v-model="searchQuery" placeholder="Tìm kiếm..." style="width: 300px;" clearable @input="debouncedSearch('pending')" />
            </div>
            <el-table
                v-loading="tableLoading"
                :data="tableData"
                max-height="500"
                style="width: 100%"
                border default-expand-all>
                <el-table-column prop="id" label="ID" min-width="70"></el-table-column>
                <el-table-column prop="employee_id" label="Mã nhân viên" min-width="140"></el-table-column>
                <el-table-column prop="user_name" label="MS" min-width="150">
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
                <el-table-column prop="user_email" label="Email" min-width="150"></el-table-column>
                <el-table-column prop="created_at" label="Ngày đăng ký" min-width="140"></el-table-column>
                <el-table-column prop="department" label="Phòng ban" min-width="130"></el-table-column>
                <el-table-column prop="team_ms" label="Team đăng ký" min-width="150"></el-table-column>
                <el-table-column prop="list_propose" label="Danh sách đề xuất" min-width="300"></el-table-column>
                <el-table-column fixed="right" label="Hành động" min-width="140">
                    <template #default="scope">
                        <div v-if="scope.row.status === 'pending'" style="display: flex; gap: 10px; flex-direction: column;">
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
        </el-tab-pane>
        <el-tab-pane label="Đã xét duyệt" name="approved" class="tab-pane-approved">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span v-html="totalText"></span>
                <el-input v-model="searchQuery" placeholder="Tìm kiếm..." style="width: 300px;" clearable @input="debouncedSearch('approved')" />
            </div>
            <el-table
                v-loading="tableLoading"
                :data="tableData"
                max-height="500"
                style="width: 100%"
                border default-expand-all>
                <el-table-column prop="id" label="ID" min-width="70"></el-table-column>
                <el-table-column prop="employee_id" label="Mã nhân viên" min-width="140"></el-table-column>
                <el-table-column prop="user_name" label="MS" min-width="150">
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
                <el-table-column prop="user_email" label="Email" min-width="150"></el-table-column>
                <el-table-column prop="created_at" label="Ngày đăng ký" min-width="140"></el-table-column>
                <el-table-column prop="department" label="Phòng ban" min-width="130"></el-table-column>
                <el-table-column prop="team_ms" label="Team đăng ký" min-width="150"></el-table-column>
                <el-table-column prop="list_propose" label="Danh sách đề xuất" min-width="300"></el-table-column>
                <el-table-column fixed="right" label="Hành động" min-width="120">
                    <template #default="scope">
                        <el-button
                            type="primary"
                            size="small"
                            @click="handleAddKPI(scope.row)">
                            Xem KPI
                        </el-button>
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
        </el-tab-pane>
        <el-tab-pane label="Đã từ chối" name="rejected" class="tab-pane-rejected">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span v-html="totalText"></span>
                <el-input v-model="searchQuery" placeholder="Tìm kiếm..." style="width: 300px;" clearable @input="debouncedSearch('rejected')" />
            </div>
            <el-table
                v-loading="tableLoading"
                :data="tableData"
                max-height="500"
                style="width: 100%"
                border default-expand-all>
                <el-table-column prop="id" label="ID" min-width="70"></el-table-column>
                <el-table-column prop="employee_id" label="Mã nhân viên" min-width="140"></el-table-column>
                <el-table-column prop="user_name" label="MS" min-width="150">
                    <template #default="scope">
                        <a :href="`${urlUserInfo}/${scope.row.user_id}/`" target="_blank">
                            {{ scope.row.user_name }}
                        </a>
                    </template>
                </el-table-column>
                <el-table-column prop="stage_id" label="Trạng thái" min-width="200">
                    <template #default="scope">
                        <el-steps
                            :active="scope.row.stage_id"
                            :finish-status="scope.row.status"
                            :class="scope.row.status"
                            simple>
                            <template v-for="n in parseInt(scope.row.max_stage)" :key="n">
                                <el-tooltip
                                    effect="dark"
                                    :content="scope.row.reviewers.find(r => r.stage_id === n)?.stage_label || `Step ${n}`"
                                    placement="top">
                                    <el-step :class="n < scope.row.stage_id ? 'customzz' : ''" :title="scope.row.reviewers.find(r => r.stage_id === n)?.stage_label || `Step ${n}`"></el-step>
                                </el-tooltip>
                            </template>
                        </el-steps>
                    </template>
                </el-table-column>
                <el-table-column prop="user_email" label="Email" min-width="150"></el-table-column>
                <el-table-column prop="created_at" label="Ngày đăng ký" min-width="140"></el-table-column>
                <el-table-column prop="department" label="Phòng ban" min-width="130"></el-table-column>
                <el-table-column prop="team_ms" label="Team đăng ký" min-width="150"></el-table-column>
                <el-table-column prop="list_propose" label="Danh sách đề xuất" min-width="300"></el-table-column>
                <el-table-column prop="comments" label="Lý do từ chối" min-width="100"></el-table-column>
                <el-table-column fixed="right" label="Hành động" min-width="120">
                    <template #default="scope">
                        <el-button
                            type="primary"
                            size="small"
                            @click="handleAddKPI(scope.row)">
                            Xem KPI
                        </el-button>
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
        </el-tab-pane>
    </el-tabs>
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
        <div class="form-wrapper" v-if="form.stage_id == form.max_stage">
            <div class="form-control">
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

                <el-form-item label="Năm:" prop="year">
                    <span v-html="yearText"></span>
                </el-form-item>
            </div>

            <div class="kpi_item" v-for="proposer in listProposer" :key="proposer.stage_id">
                <el-form-item label="Người đề nghị KPI:" prop="stage">
                    <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == proposer.stage_id).reviewer_id}/`" target="_blank">
                        {{ form.reviewers.find(reviewer => reviewer.stage_id.toString() === proposer.stage_id)?.stage_label }}
                    </a>
                </el-form-item>
                <div class="form-table">
                    <el-table :data="proposer.stage_id == 3 ? tableDataKpiMSA : tableDataKpiHR" border style="width: 100%" max-height="500">
                        <el-table-column fixed prop="program" label="Chương trình" min-width="140"></el-table-column>
                        <template v-for="month in 12" :key="month">
                            <el-table-column :prop="'month' + month" :label="'T' + month" min-width="70">
                                <template #default="scope">
                                    <el-input
                                        :disabled="proposer.stage_id == 3 ? !editKpiMSA || month <= currMonth : !editKpiHR || month <= currMonth"
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
                            v-if="proposer.stage_id == 3 ? editKpiMSA : editKpiHR"
                            fixed="right"
                            label="Hành động"
                            min-width="100">
                            <template #default="scope">
                                <el-button
                                    link
                                    type="primary"
                                    size="small"
                                    @click.prevent="deleteRow(scope.$index, scope.row.program, proposer.label)">
                                    Xoá
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <el-select v-if="proposer.stage_id == 3 ? editKpiMSA : editKpiHR" class="mt-4" style="width: 100%" placeholder="Chọn chương trình">
                        <el-option
                            v-for="program in listProgram"
                            @click="onAddItem(program, proposer.label)"
                            :key="program"
                            :label="program"
                            :value="program">
                        </el-option>
                    </el-select>
                </div>
                <el-form-item class="change-kpi_btn">
                    <el-button
                        type="primary"
                        @click="changeKpi(proposer)"
                        :loading=""
                        :disabled="">
                        <span v-html="textBtn4"></span>
                    </el-button>
                </el-form-item>

                <div class="timeline-collapse">
                    <el-collapse v-model="activeNames" @change="handleChange">
                        <el-collapse-item title="Lịch sử thay đổi" name="1">
                            <el-timeline style="max-width: 600px">
                                <el-timeline-item timestamp="2018/4/12" placement="top">
                                    <el-card>
                                        <h4>Update Github template</h4>
                                        <p>Tom committed 2018/4/12 20:46</p>
                                    </el-card>
                                </el-timeline-item>
                                <el-timeline-item timestamp="2018/4/3" placement="top">
                                    <el-card>
                                        <h4>Update Github template</h4>
                                        <p>Tom committed 2018/4/3 20:46</p>
                                    </el-card>
                                </el-timeline-item>
                                <el-timeline-item timestamp="2018/4/2" placement="top">
                                    <el-card>
                                        <h4>Update Github template</h4>
                                        <p>Tom committed 2018/4/2 20:46</p>
                                    </el-card>
                                </el-timeline-item>
                            </el-timeline>
                        </el-collapse-item>
                    </el-collapse>
                </div>
            </div>
            <div class="user_confirm" v-if="!editKpiMSA && !editKpiHR">
                <el-checkbox v-model="form.agree_kpi">
                    Tôi đồng ý với các KPI được phân công ở bảng trên
                    <span v-html="agreeKpiText"></span>
                </el-checkbox>
            </div>

            <div class="form-btn">
                <el-form-item v-if="!editKpiMSA && !editKpiHR">
                    <el-button
                        type="primary"
                        @click="finalSubmit()"
                        :loading="loading"
                        :disabled="loading || !isFormValid">
                        <span v-html="textBtn1"></span>
                    </el-button>
                </el-form-item>

                <el-form-item v-else>
                    <el-button
                        type="primary"
                        @click="handleDealKpi()"
                        :loading="loading"
                        :disabled="loading">
                        <span v-html="textBtn1"></span>
                    </el-button>
                </el-form-item>
            </div>
        </div>
        <div class="form-wrapper-x" v-else>
            <div class="form-control">
                <el-form-item label="Người đề nghị KPI:" prop="stage">
                    <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == form.stage_id).reviewer_id}/`" target="_blank">
                        {{ form.stage }}
                    </a>
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
                <el-form-item label="Năm:" prop="year">
                    <span v-html="yearText"></span>
                </el-form-item>
            </div>
            <div class="form-table">
                <el-table :data="tableDataKpi" border style="width: 100%" max-height="500">
                    <el-table-column fixed prop="program" label="Chương trình" min-width="140"></el-table-column>
                    <template v-for="month in 12" :key="month">
                        <el-table-column :prop="'month' + month" :label="'T' + month" min-width="70">
                            <template #default="scope">
                                <el-input
                                    :disabled="month <= currMonth"
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
                        fixed="right"
                        label="Hành động"
                        min-width="100">
                        <template #default="scope">
                            <el-button
                                link
                                type="primary"
                                size="small"
                                @click.prevent="deleteRow(scope.$index, scope.row.program)">
                                Xoá
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-select class="mt-4" style="width: 100%" placeholder="Chọn chương trình">
                    <el-option
                        v-for="program in listProgram"
                        @click="onAddItem(program)"
                        :key="program"
                        :label="program"
                        :value="program">
                    </el-option>
                </el-select>
            </div>
            <div class="form-btn">
                <el-form-item>
                    <el-button
                        type="primary"
                        @click="submitForm('create and approve')"
                        :loading="approveLoading"
                        :disabled="approveLoading">
                        <span v-html="textBtn2"></span>
                    </el-button>
                </el-form-item>
            </div>
        </div>

    </el-form>
</div>