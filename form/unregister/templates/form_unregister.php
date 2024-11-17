<div v-if="!msId" class="manager-notice">
  <el-alert
    type="warning"
    :closable="false"
    show-icon>
    <template #title>
      Bạn chưa là MS, không thể hủy đăng ký hoặc chuyển team. Nếu bạn muốn đăng ký làm MS, vui lòng <a href="<?php echo $config['base_url'] . '/' . $config['root_folder'] . '/form/register/'?>" target='_blank'>click vào đây</a>
    </template>
  </el-alert>
</div>


<div v-if="msId" class="manager-notice">
  <!-- <div id="loader">
        <span span class="loader"></span>
    </div> -->
  <div class="title">
    <h1 style="font-size:25px;margin:10px 0;" class="title-page">Form hủy đăng ký/chuyển team MS</h1>
  </div>

  <div class="form-unregister">
    <el-form :model="form" :rules="rules" ref="ruleFormRef" label-width="auto" style="width: 800px; margin-top: 32px;">

      <el-form-item label="Họ và tên:" prop="name">
        <el-input v-model="form.name" :disabled="true" />
      </el-form-item>

      <el-form-item label="Team được phân bổ:" prop="curr_team_ms">
        <el-input v-model="form.curr_team_ms" :disabled="true" />
      </el-form-item>

      <el-form-item label="Ngày đăng ký:" prop="date_register">
        <el-date-picker
          v-model="form.date_register"
          type="datetime"
          placeholder="Chọn ngày đăng ký"
          value-format="dd/MM/yyyy HH:mm:ss"
          :disabled="false">
        </el-date-picker>
      </el-form-item>

      <el-form-item label="Phân loại MS:" prop="type_ms">
        <el-input v-model="form.type_ms" :disabled="true" />
      </el-form-item>

      <el-form-item label="Chuyển team:" prop="transfer_team">
        <el-checkbox v-model="form.transfer_team" @change="handleTransferTeamChange">Tôi muốn chuyển team</el-checkbox>
      </el-form-item>

      <el-form-item label="Chọn team mới:" prop="team_ms" v-if="form.transfer_team">
        <el-select v-model="form.team_ms" placeholder="Select">
          <el-option
            v-for="value in listTeamMS"
            :key="value.ID"
            :label="value.NAME"
            :value="value.ID">
          </el-option>
        </el-select>
      </el-form-item>

      <el-form-item label="Nhập lý do:" prop="reason">
        <el-input
          type="textarea"
          v-model="form.reason"
          :rows="5"
          placeholder="Nhập lý do thôi làm MS/chuyển team">
        </el-input>
      </el-form-item>

      <el-form-item prop="checkbox">
        <el-checkbox
          v-model="form.checkbox"
          true-label="Tôi CAM KẾT bảo mật thông tin sau khi hủy đăng ký/chuyển team MS"
          false-label="">
          Tôi CAM KẾT bảo mật thông tin sau khi hủy đăng ký/chuyển team MS
        </el-checkbox>
      </el-form-item>

      <el-form-item>
        <el-button
          type="primary"
          @click="submitForm"
          :loading="loading"
          :disabled="loading || !isFormValid">
          Gửi
        </el-button>
      </el-form-item>
    </el-form>
  </div>
</div>