<div class="title">
    <h1 style="font-size:25px;margin:10px 0;" class="title-page">Form KPI</h1>
</div>
<div class="form-kpi">
    <el-form :model="form" :rules="rules" ref="ruleFormRef" label-width="auto" style="width: 100%; margin-top: 32px;">
        <div class="form-control">
            <el-form-item label="Người đề nghị KPI:" prop="proposer">
                <el-select  v-model="form.proposer" placeholder="Select">
                    <el-option
                        v-for="proposer in listProposer"
                        :key="proposer.id"
                        :label="proposer.name"
                        :value="proposer.id"
                        @click="getTableData()"
                         />
                </el-select>
            </el-form-item>
            <el-form-item label="Người nhận KPI:" prop="manager">
                <template v-if="selectedManager">
                    <div class="selected-manager">
                        <div class="manager-left">
                            <div class="manager-avatar">
                                <el-avatar
                                    :size="30"
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
                </template>
                <template v-else>
                    <el-input
                        v-model="searchManager"
                        placeholder="Tìm kiếm"
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
                                    :size="30"
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
                <el-select v-model="form.team_ms" placeholder="Select">
                    <el-option
                        v-for="team in listTeamMS"
                        :key="team.ID"
                        :label="team.NAME"
                        @click="getTableData()"
                        :value="team.ID" />
                </el-select>
            </el-form-item>
        </div>
        <div class="form-table">
            <el-table :data="tableData" border style="width: 100%" max-height="250">
                <el-table-column fixed prop="program" label="Chương trình" width="150"></el-table-column>
                <template v-for="month in 12" :key="month">
                    <el-table-column :prop="'month' + month" :label="'M' + month" width="120">
                        <template #default="scope">
                            <el-input
                                type="number"
                                size="small"
                                v-model="scope.row['m' + month]"
                                @input="(event) => handleInputChange(scope.$index, month, event)"
                                placeholder="0"
                                >
                            </el-input>
                        </template>
                    </el-table-column>
                </template>
                <el-table-column v-if="!operation" fixed="right" label="Operations" min-width="120">
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
            <el-select class="mt-4" style="width: 100%" placeholder="Chọn chương trình" :disabled="!checkEnabelSelect()">
                <el-option
                    v-for="program in program"
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
                    @click="submitForm"
                    :loading="loading"
                    :disabled="loading">
                    Gửi
                </el-button>
            </el-form-item>
        </div>
    </el-form>
</div>