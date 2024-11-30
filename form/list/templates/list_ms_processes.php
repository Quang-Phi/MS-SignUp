<div class="title">
    <h1 style="font-size:25px;margin:10px 0;" class="title-page" v-html="pageTitle"></h1>
</div>

<div :class="showFormKPI ? 'overlay active' : 'overlay'" @click="hideOverlay()"></div>
<div :class="showKPI ? 'overlay_kpi active' : 'overlay_kpi'" @click="hideKPIsMember()"></div>

<div class="list-ms-processes" style="position: relative;">
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
                                        <template v-if="!rejectLoading[scope.row.id]">
                                            <el-button
                                                type="primary"
                                                size="small"
                                                @click="handleAddKPI(scope.row)">
                                                Xem KPI
                                            </el-button>
                                        </template>
                                    </template>

                                    <template v-else>
                                        <template v-if="!rejectLoading[scope.row.id]">
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
                                    <template v-if="!rejectLoading[scope.row.id]">
                                        <el-button
                                            type="success"
                                            size="small"
                                            :loading="approveLoading[scope.row.id]"
                                            @click="handleApprove(scope.row, null, true, true)">
                                            Xét duyệt
                                        </el-button>
                                    </template>
                                </template>

                                <template v-if="!approveLoading[scope.row.id] && scope.row.completed == false">
                                    <el-button
                                        type="danger"
                                        size="small"
                                        :loading="rejectLoading[scope.row.id]"
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
                <el-table-column prop="join_date" label="Ngày tham gia MS" min-width="140"></el-table-column>
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
                            v-if="checkShowKPI(scope.row.reviewers, scope.row.stage_id)"
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
    <div class="tab-extra" style="margin-top: 8px; position: absolute; right: 0; top: 0">
        <el-button type="primary" @click="handleClickShowGoal()"><span v-html="goalText"></span></el-button>
    </div>
</div>

<div :class="showFormKPI ? 'form-kpi active' : 'form-kpi'">
    <div :class="loadingKPI ? 'loader-wrap active' : 'loader-wrap'">
        <span class="loader"></span>
    </div>

    <div class="side-panel-labels">
        <div class="side-panel-label" style="max-width: 215px;">
            <div class="side-panel-label-icon-box" title="Close" @click="hideOverlay()">
                <div class="side-panel-label-icon side-panel-label-icon-close"></div>
            </div><span class="side-panel-label-text"></span>
        </div>
    </div>

    <el-form v-if="form.status == 'pending'"
        :model=" form"
        :rules="rules"
        ref="ruleFormRef"
        label-width="auto"
        style="margin-top: 32px;">
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
                <el-form-item label="Bảng KPI:" prop="stage">
                    <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == proposer.stage_id).reviewer_id}/`" target="_blank">
                        <span v-html="textReviewerName2(proposer)"></span>
                    </a>
                </el-form-item>

                <div class="form-table">
                    <el-table
                        :data="proposer.stage_id == 3 ? tableDataKpiMSA : tableDataKpiHR"
                        border
                        style="width: 100%"
                        max-height="500"
                        :show-summary="proposer.stage_id != 4"
                        :summary-method="getSummaries">
                        <el-table-column fixed prop="program" label="Chương trình" min-width="140"></el-table-column>
                        <template v-for="month in 12" :key="month">
                            <el-table-column :prop="`m${month}`" :label="'T' + month" min-width="70">
                                <template #default="scope">
                                    <el-input
                                        :class='getClass(scope.row.program, scope.row[`m${month}`], month, proposer.stage_id)'
                                        :disabled="form.completed == true ? (proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR) : (currMonth < 12 ? month <= currMonth || (proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR) : (proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR))"
                                        size="small"
                                        v-model="scope.row[`m${month}`]"
                                        @input="(event) => handleInputChange(scope.$index, month, event, proposer)"
                                        placeholder="0">
                                    </el-input>
                                </template>
                            </el-table-column>
                        </template>

                        <el-table-column fixed="right" label="Tổng" min-width="60">
                            <template #default="scope">
                                {{ calculateRowTotal(scope.row) }}
                            </template>
                        </el-table-column>

                        <el-table-column
                            v-if="proposer.stage_id == 3 ? editKpiMSA : editKpiHR"
                            fixed="right"
                            label="Hành động"
                            min-width="100">
                            <template #default="scope">
                                <el-button
                                    link
                                    :disabled="!checkShowDelete(scope.row.program)"
                                    type="primary"
                                    size="small"
                                    @click.prevent="deleteRow(scope.$index, scope.row.program, proposer.label)">
                                    Xoá
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <el-select v-if="proposer.stage_id == 3 ? editKpiMSA : false" class="mt-4" style="width: 100%" placeholder="Chọn chương trình">
                        <el-option
                            v-for="program in listProgram"
                            @click="onAddItem(program, proposer.label)"
                            :key="program"
                            :label="program"
                            :value="program">
                        </el-option>
                    </el-select>
                </div>

                <el-form-item v-if="form.completed == true ? proposer.stage_id == 3 ? form.flag_edit_3 == true : form.flag_edit_4 == true : true" class="change-kpi_btn">
                    <el-popconfirm
                        title="Điều chỉnh sẽ cần duyệt lại, xác nhận?"
                        @confirm="changeKpi(proposer, form)">
                        <template #reference>
                            <el-button
                                type="primary"
                                :disabled="loading">
                                <span v-html="textBtn4"></span>
                            </el-button>
                        </template>
                    </el-popconfirm>
                </el-form-item>

                <div class="infinite-list-wrapper"
                    v-infinite-scroll="() => load(proposer.stage_id)"
                    :infinite-scroll-disabled="isDisabled(proposer.stage_id)"
                    :infinite-scroll-distance="20"
                    :infinite-scroll-immediate="false"
                    style="overflow:auto; max-height: 600px">
                    <div v-if="hasTimeline" class="timeline-collapse">
                        <el-collapse
                            :model-value="activeNames"
                            @change="(val) => handleChange(val, proposer.stage_id)">
                            <el-collapse-item
                                :title="'Lịch sử thay đổi KPI - ' + proposer.label"
                                :name="proposer.stage_id">
                                <div v-loading="timelineLoading" element-loading-text="Loading...">
                                    <el-timeline style="max-width: 900px">
                                        <el-timeline-item
                                            v-for="(item, index) in timelineData[proposer.stage_id]"
                                            :key="`${proposer.stage_id}_${item.id}_${index}`"
                                            :timestamp="item.created_at"
                                            placement="top">
                                            <el-card>
                                                <template #header>
                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                        <a :href="`${urlUserInfo}/${item.user_id}/`" target="_blank">
                                                            {{ item.user_name }}
                                                        </a>
                                                    </div>
                                                </template>
                                                <el-table
                                                    :data="JSON.parse(item.old_kpi || '[]')"
                                                    border
                                                    style="width: 100%"
                                                    max-height="500">
                                                    <el-table-column
                                                        fixed
                                                        prop="program"
                                                        label="Chương trình"
                                                        min-width="140">
                                                    </el-table-column>
                                                    <template v-for="month in 12" :key="month">
                                                        <el-table-column
                                                            :prop="'m' + month"
                                                            :label="'T' + month"
                                                            min-width="50">
                                                            <template #default="scope">
                                                                <span>{{ scope.row['m' + month] || 0 }}</span>
                                                            </template>
                                                        </el-table-column>
                                                    </template>
                                                </el-table>
                                            </el-card>
                                        </el-timeline-item>
                                    </el-timeline>

                                    <div v-if="timelineLoading" class="loading-more">
                                        <span v-html="timelineLoadingText"></span>
                                    </div>
                                    <div v-if="noMore[proposer.stage_id]" class="no-more">
                                        <span v-html="noMoreText"></span>
                                    </div>
                                </div>
                            </el-collapse-item>
                        </el-collapse>
                    </div>
                </div>
            </div>

            <div class="user_confirm" v-if="!editKpiMSA && !editKpiHR">
                <el-checkbox v-model="form.agree_kpi">
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

        <div class="form-wrapper" v-else>
            <div class="form-control">
                <el-form-item label="Bảng KPI:" prop="stage">
                    <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == form.stage_id).reviewer_id}/`" target="_blank">
                        <span v-html="textReviewerName"></span>
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

            <div class="kpi_item">
                <div v-if="form.stage_id == 4">
                    <el-form-item label="Bảng KPI MSA (tham khảo):" prop="stage">
                    </el-form-item>

                    <div class="form-table-msa">
                        <el-table
                            :data="tableDataKpiMSA"
                            border
                            style="width: 100%"
                            max-height="500"
                            :show-summary="true"
                            :summary-method="getSummaries">
                            <el-table-column fixed prop="program" label="Chương trình" width="140"></el-table-column>
                            <template v-for="month in 12" :key="month" style="display: none">
                                <el-table-column :prop="`m${month}`" :label="'T' + month" min-width="70">
                                    <template #default="scope">
                                        <el-input
                                            class="custom"
                                            :disabled="true"
                                            size="small"
                                            v-model="scope.row[`m${month}`]"
                                            placeholder="0">
                                        </el-input>
                                    </template>
                                </el-table-column>
                            </template>
                        </el-table>
                    </div>

                    <el-form-item label="Bảng nhập KPI:" prop="stage" style="margin-top: 20px">
                    </el-form-item>
                </div>

                <div class="form-table">
                    <el-table
                        ref="tableRef"
                        :data="tableDataKpi"
                        border
                        style="width: 100%"
                        max-height="500"
                        :show-summary="form.stage_id != 4"
                        :summary-method="getSummaries">
                        <el-table-column fixed prop="program" label="Chương trình" width="140"></el-table-column>
                        <template
                            v-for="month in 12"
                            :key="month">
                            <el-table-column :prop="`m${month}`" :label="'T' + month" min-width="70">
                                <template #default="scope">
                                    <el-input
                                        :class="getClass(scope.row.program, scope.row[`m${month}`], month)"
                                        :disabled="form.completed == true ? false : (currMonth < 12 ? month <= currMonth : false)"
                                        size="small"
                                        v-model="scope.row[`m${month}`]"
                                        @input="(event) => handleInputChange(scope.$index, month, event, form)"
                                        placeholder="0">
                                    </el-input>
                                </template>
                            </el-table-column>
                        </template>

                        <el-table-column fixed="right" label="Tổng" min-width="60">
                            <template #default="scope">
                                {{ calculateRowTotal(scope.row) }}
                            </template>
                        </el-table-column>

                        <el-table-column
                            v-if="form.stage_id != 4"
                            fixed="right"
                            label="Hành động"
                            min-width="100">
                            <template #default="scope">
                                <el-button
                                    link
                                    type="primary"
                                    size="small"
                                    :disabled="!checkShowDelete(scope.row.program)"
                                    @click.prevent="deleteRow(scope.$index, scope.row.program)">
                                    Xoá
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <el-select v-if="form.stage_id != 4" class="mt-4" style="width: 100%" placeholder="Chọn chương trình">
                        <el-option
                            v-for="program in listProgram"
                            @click="onAddItem(program)"
                            :key="program"
                            :label="program"
                            :value="program">
                        </el-option>
                    </el-select>
                </div>
            </div>

            <div v-if="form.stage_deal" class="infinite-list-wrapper"
                v-infinite-scroll="() => load(form.stage_id)"
                :infinite-scroll-disabled="isDisabled(form.stage_id)"
                :infinite-scroll-distance="20"
                :infinite-scroll-immediate="false"
                style="overflow:auto; max-height: 600px">
                <div v-if="hasTimeline" class="timeline-collapse">
                    <el-collapse
                        :model-value="activeNames"
                        @change="(val) => handleChange(val, form.stage_id)">
                        <el-collapse-item
                            :title="'Lịch sử thay đổi KPI'"
                            :name="form.stage_id">
                            <div v-loading="timelineLoading" element-loading-text="Loading...">
                                <el-timeline style="max-width: 900px">
                                    <el-timeline-item
                                        v-for="(item, index) in timelineData[form.stage_id]"
                                        :key="`${form.stage_id}_${item.id}_${index}`"
                                        :timestamp="item.created_at"
                                        placement="top">
                                        <el-card>
                                            <template #header>
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <a :href="`${urlUserInfo}/${item.user_id}/`" target="_blank">
                                                        {{ item.user_name }}
                                                    </a>
                                                </div>
                                            </template>
                                            <el-table
                                                :data="JSON.parse(item.old_kpi || '[]')"
                                                border
                                                style="width: 100%"
                                                max-height="500">
                                                <el-table-column
                                                    fixed
                                                    prop="program"
                                                    label="Chương trình"
                                                    min-width="140">
                                                </el-table-column>
                                                <template v-for="month in 12" :key="month">
                                                    <el-table-column
                                                        :prop="'m' + month"
                                                        :label="'T' + month"
                                                        min-width="50">
                                                        <template #default="scope">
                                                            <span>{{ scope.row['m' + month] || 0 }}</span>
                                                        </template>
                                                    </el-table-column>
                                                </template>
                                            </el-table>
                                        </el-card>
                                    </el-timeline-item>
                                </el-timeline>

                                <div v-if="timelineLoading" class="loading-more">
                                    <span v-html="timelineLoadingText"></span>
                                </div>
                                <div v-if="noMore[form.stage_id]" class="no-more">
                                    <span v-html="noMoreText"></span>
                                </div>
                            </div>
                        </el-collapse-item>
                    </el-collapse>
                </div>
            </div>

            <div class="form-btn" style="margin-top: 20px">
                <el-form-item>
                    <el-button
                        type="primary"
                        @click="submitForm()"
                        :loading="loading"
                        :disabled="loading">
                        <span v-html="textBtn2"></span>
                    </el-button>
                </el-form-item>
            </div>
        </div>
    </el-form>

    <el-form v-if="form.status == 'success'"
        :model="form"
        :rules="rules"
        ref="ruleFormRef"
        label-width="auto"
        style="margin-top: 32px;">
        <div class="form-wrapper">
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

                <el-form-item v-if="checkViewerMemberKpi(form)">
                    <el-button
                        style="position: absolute; right: 0"
                        type="primary"
                        @click="showKpisMember()">
                        <span v-html="btnKpisMemberText"></span>
                    </el-button>
                </el-form-item>
            </div>

            <div class="kpi_item" v-for="proposer in listProposer" :key="proposer.stage_id">
                <el-form-item label="Bảng KPI:" prop="stage">
                    <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == proposer.stage_id).reviewer_id}/`" target="_blank">
                        <span v-html="textReviewerName2(proposer)"></span>
                    </a>
                </el-form-item>

                <div class="form-table">
                    <el-table
                        :data="proposer.stage_id == 3 ? tableDataKpiMSA : tableDataKpiHR"
                        border
                        style="width: 100%"
                        max-height="500"
                        :show-summary="proposer.stage_id != 4"
                        :summary-method="getSummaries">
                        <el-table-column fixed prop="program" label="Chương trình" min-width="140"></el-table-column>
                        <template v-for="month in 12" :key="month">
                            <el-table-column :prop="`m${month}`" :label="'T' + month" min-width="70">
                                <template #default="scope">
                                    <el-input
                                        class="custom"
                                        :disabled="proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR"
                                        size="small"
                                        v-model="scope.row[`m${month}`]"
                                        @input="(event) => handleInputChange(scope.$index, month, event, proposer)"
                                        placeholder="0">
                                    </el-input>
                                </template>
                            </el-table-column>
                        </template>

                        <el-table-column fixed="right" label="Tổng" min-width="60">
                            <template #default="scope">
                                {{ calculateRowTotal(scope.row) }}
                            </template>
                        </el-table-column>

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
                                    :disabled="!checkShowDelete(scope.row.program)"
                                    @click.prevent="deleteRow(scope.$index, scope.row.program, proposer.label)">
                                    Xoá
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <el-select v-if="proposer.stage_id == 3 ? editKpiMSA : false" class="mt-4" style="width: 100%" placeholder="Chọn chương trình">
                        <el-option
                            v-for="program in listProgram"
                            @click="onAddItem(program, proposer.label)"
                            :key="program"
                            :label="program"
                            :value="program">
                        </el-option>
                    </el-select>
                </div>

                <el-form-item v-if="checkChangeKpi(proposer, form)" class="change-kpi_btn">
                    <el-popconfirm
                        title="Điều chỉnh sẽ cần duyệt lại, xác nhận?"
                        @confirm="changeKpi(proposer, form)">
                        <template #reference>
                            <el-button
                                :disabled="loading"
                                type="primary">
                                <span v-html="textBtn4"></span>
                            </el-button>
                        </template>
                    </el-popconfirm>
                </el-form-item>

                <div class="infinite-list-wrapper"
                    v-infinite-scroll="() => load(proposer.stage_id)"
                    :infinite-scroll-disabled="isDisabled(proposer.stage_id)"
                    :infinite-scroll-distance="20"
                    :infinite-scroll-immediate="false"
                    style="overflow:auto; max-height: 600px">
                    <div v-if="hasTimeline" class="timeline-collapse">
                        <el-collapse
                            :model-value="activeNames"
                            @change="(val) => handleChange(val, proposer.stage_id)">
                            <el-collapse-item
                                :title="'Lịch sử thay đổi KPI - ' + proposer.label"
                                :name="proposer.stage_id">
                                <div v-loading="timelineLoading" element-loading-text="Loading...">
                                    <el-timeline style="max-width: 900px">
                                        <el-timeline-item
                                            v-for="(item, index) in timelineData[proposer.stage_id]"
                                            :key="`${proposer.stage_id}_${item.id}_${index}`"
                                            :timestamp="item.created_at"
                                            placement="top">
                                            <el-card>
                                                <template #header>
                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                        <a :href="`${urlUserInfo}/${item.user_id}/`" target="_blank">
                                                            {{ item.user_name }}
                                                        </a>
                                                    </div>
                                                </template>
                                                <el-table
                                                    :data="JSON.parse(item.old_kpi || '[]')"
                                                    border
                                                    style="width: 100%"
                                                    max-height="500">
                                                    <el-table-column
                                                        fixed
                                                        prop="program"
                                                        label="Chương trình"
                                                        min-width="140">
                                                    </el-table-column>
                                                    <template v-for="month in 12" :key="month">
                                                        <el-table-column
                                                            :prop="'m' + month"
                                                            :label="'T' + month"
                                                            min-width="50">
                                                            <template #default="scope">
                                                                <span>{{ scope.row['m' + month] || 0 }}</span>
                                                            </template>
                                                        </el-table-column>
                                                    </template>
                                                </el-table>
                                            </el-card>
                                        </el-timeline-item>
                                    </el-timeline>

                                    <div v-if="timelineLoading" class="loading-more">
                                        <span v-html="timelineLoadingText"></span>
                                    </div>
                                    <div v-if="noMore[proposer.stage_id]" class="no-more">
                                        <span v-html="noMoreText"></span>
                                    </div>
                                </div>
                            </el-collapse-item>
                        </el-collapse>
                    </div>
                </div>
            </div>

            <div v-if="editKpiMSA || editKpiHR" class="form-btn">
                <el-form-item>
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
    </el-form>

    <el-form v-if="form.status == 'error'"
        :model="form"
        :rules="rules"
        ref="ruleFormRef"
        label-width="auto"
        style="margin-top: 32px;">
        <div class="form-wrapper">
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
                <div v-if="proposer.stage_id <= form.stage_id || (proposer.stage_id == 3 ? count(tableDataKpiMSA) > 0 : false)">
                    <el-form-item label="Bảng KPI:" prop="stage">
                        <a :href="`${urlUserInfo}/${form.reviewers.find(reviewer => reviewer.stage_id == proposer.stage_id).reviewer_id}/`" target="_blank">
                            <span v-html="textReviewerName2(proposer)"></span>
                        </a>
                    </el-form-item>

                    <div class="form-table">
                        <el-table
                            :data="proposer.stage_id == 3 ? tableDataKpiMSA : tableDataKpiHR"
                            border
                            style="width: 100%"
                            :show-summary="proposer.stage_id != 4"
                            :summary-method="getSummaries"
                            max-height="500">
                            <el-table-column fixed prop="program" :label="proposer.stage_id == 3 ? 'Chương trình' : 'Tháng'" min-width="140"></el-table-column>
                            <template v-for="month in 12" :key="month">
                                <el-table-column :prop="`m${month}`" :label="'T' + month" min-width="70">
                                    <template #default="scope">
                                        <el-input
                                            class="custom"
                                            :disabled="true"
                                            size="small"
                                            v-model="scope.row[`m${month}`]"
                                            placeholder="0">
                                        </el-input>
                                    </template>
                                </el-table-column>
                            </template>
                            <el-table-column fixed="right" label="Tổng" min-width="60">
                                <template #default="scope">
                                    {{ calculateRowTotal(scope.row) }}
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                    <div class="infinite-list-wrapper"
                        v-infinite-scroll="() => load(proposer.stage_id)"
                        :infinite-scroll-disabled="isDisabled(proposer.stage_id)"
                        :infinite-scroll-distance="20"
                        :infinite-scroll-immediate="false"
                        style="overflow:auto; max-height: 600px">
                        <div v-if="hasTimeline" class="timeline-collapse">
                            <el-collapse
                                :model-value="activeNames"
                                @change="(val) => handleChange(val, proposer.stage_id)">
                                <el-collapse-item
                                    :title="'Lịch sử thay đổi KPI - ' + proposer.label"
                                    :name="proposer.stage_id">
                                    <div v-loading="timelineLoading" element-loading-text="Loading...">
                                        <el-timeline style="max-width: 900px">
                                            <el-timeline-item
                                                v-for="(item, index) in timelineData[proposer.stage_id]"
                                                :key="`${proposer.stage_id}_${item.id}_${index}`"
                                                :timestamp="item.created_at"
                                                placement="top">
                                                <el-card>
                                                    <template #header>
                                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                                            <a :href="`${urlUserInfo}/${item.user_id}/`" target="_blank">
                                                                {{ item.user_name }}
                                                            </a>
                                                        </div>
                                                    </template>
                                                    <el-table
                                                        :data="JSON.parse(item.old_kpi || '[]')"
                                                        border
                                                        style="width: 100%"
                                                        max-height="500">
                                                        <el-table-column
                                                            fixed
                                                            prop="program"
                                                            label="Chương trình"
                                                            min-width="140">
                                                        </el-table-column>
                                                        <template v-for="month in 12" :key="month">
                                                            <el-table-column
                                                                :prop="'m' + month"
                                                                :label="'T' + month"
                                                                min-width="50">
                                                                <template #default="scope">
                                                                    <span>{{ scope.row['m' + month] || 0 }}</span>
                                                                </template>
                                                            </el-table-column>
                                                        </template>
                                                    </el-table>
                                                </el-card>
                                            </el-timeline-item>
                                        </el-timeline>

                                        <div v-if="timelineLoading" class="loading-more">
                                            <span v-html="timelineLoadingText"></span>
                                        </div>
                                        <div v-if="noMore[proposer.stage_id]" class="no-more">
                                            <span v-html="noMoreText"></span>
                                        </div>
                                    </div>
                                </el-collapse-item>
                            </el-collapse>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </el-form>
</div>

<div :class="showKPI ? 'member-kpi active' : 'member-kpi'">
    <div :class="loadingMemberKPI ? 'loader-wrap active' : 'loader-wrap'">
        <span class="loader"></span>
    </div>
    <div class="side-panel-labels">
        <div class="side-panel-label" style="max-width: 215px;">
            <div class="side-panel-label-icon-box" title="Close" @click="hideKPIsMember()">
                <div class="side-panel-label-icon side-panel-label-icon-close"></div>
            </div><span class="side-panel-label-text"></span>
        </div>
    </div>
    <el-tabs v-model="activeNameKpi" @tab-click="handleClickTab">
        <el-tab-pane v-for="(member, index) in teamMsMember" :key="index" :label="member.LAST_NAME + ' ' + member.NAME" :name="member.ID">
            <div class="kpi_item" v-for="proposer in listProposer" :key="proposer.stage_id">
                <el-form-item label="Bảng KPI:" prop="stage">
                    <span v-html="textReviewerName2(proposer)"></span>
                </el-form-item>

                <div class="form-table">
                    <el-table
                        :data="proposer.stage_id == 3 ? tableDataKpiMemberMSA : tableDataKpiMemberHR"
                        border
                        style="width: 100%"
                        max-height="500"
                        :show-summary="proposer.stage_id != 4"
                        :summary-method="getSummaries">
                        <el-table-column fixed prop="program" label="Chương trình" min-width="140"></el-table-column>
                        <template v-for="month in 12" :key="month">
                            <el-table-column :prop="`m${month}`" :label="'T' + month" min-width="70">
                                <template #default="scope">
                                    <el-input
                                        class="custom" 
                                        :disabled="form.completed == true ? (proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR) : (currMonth < 12 ? month <= currMonth || (proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR) : (proposer.stage_id == 3 ? !editKpiMSA : !editKpiHR))"
                                        size="small"
                                        v-model="scope.row[`m${month}`]"
                                        @input="(event) => handleInputChange(scope.$index, month, event, proposer)"
                                        placeholder="0">
                                    </el-input>
                                </template>
                            </el-table-column>
                        </template>

                        <el-table-column fixed="right" label="Tổng" min-width="60">
                            <template #default="scope">
                                {{ calculateRowTotal(scope.row) }}
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </div>
        </el-tab-pane>
    </el-tabs>
</div>

<el-drawer
    v-model="drawer"
    :with-header="false"
    style="overflow: unset;">
    <div class="side-panel-labels">
        <div class="side-panel-label" style="max-width: 215px;">
            <div class="side-panel-label-icon-box" title="Close" @click="drawer = false">
                <div class="side-panel-label-icon side-panel-label-icon-close"></div>
            </div><span class="side-panel-label-text"></span>
        </div>
    </div>
    <div class="content">
        <div class="table-control" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div class="title" v-html="titleGoal"></div>
            <div class="block">
                <el-date-picker
                    v-model="datePicker"
                    @change="handlePickMonth"
                    type="monthrange"
                    range-separator="-"
                    start-placeholder="Tháng bắt đầu"
                    end-placeholder="Tháng kết thúc">
                </el-date-picker>
            </div>
        </div>
        <el-table
            :data="tableDataAll"
            style="width: 100%">
            <el-table-column fixed prop="user_name" label="MS" min-width="150">
                <template #default="scope">
                    <a :href="`${urlUserInfo}/${scope.row.user_id}/`" target="_blank">
                        {{ scope.row.user_name }}
                    </a>
                </template>
            </el-table-column>

            <el-table-column
                v-for="program in listProgram" :key="program"
                :prop="program"
                :label="program"
                width="150">
            </el-table-column>
        </el-table>
    </div>
</el-drawer>