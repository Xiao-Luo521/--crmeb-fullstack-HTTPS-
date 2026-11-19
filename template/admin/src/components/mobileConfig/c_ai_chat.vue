<template>
  <div class="ai-chat-config">
    <el-form label-width="86px" label-position="left" size="small">
      <div class="section-title">内容设置</div>
      <el-form-item label="选择Agent">
        <el-select v-model="configObj.agentConfig.value" placeholder="请选择要使用的Agent" style="width: 100%" @change="onAgentChange">
          <el-option v-for="item in agentList" :key="item.id" :label="item.name" :value="String(item.id)" />
        </el-select>
        <div class="tips" v-if="!agentList.length">暂无启用的Agent，请先到「Agent」页面添加</div>
        <div class="tips" v-else>聊天记录按登录会员分别保存</div>
      </el-form-item>

      <el-form-item label="显示标题">
        <el-switch v-model="showTitle" :active-value="1" :inactive-value="0" />
      </el-form-item>
      <el-form-item label="组件标题" v-if="showTitle === 1">
        <el-input v-model="configObj.titleConfig.value" :maxlength="configObj.titleConfig.max" placeholder="请输入标题" />
      </el-form-item>
      <el-form-item label="欢迎语">
        <el-input
          type="textarea"
          :rows="2"
          v-model="configObj.welcomeConfig.value"
          :maxlength="configObj.welcomeConfig.max"
          placeholder="请输入欢迎语"
        />
      </el-form-item>
      <el-form-item label="输入框提示">
        <el-input v-model="configObj.tipConfig.value" :maxlength="configObj.tipConfig.max" placeholder="请输入提示文字" />
      </el-form-item>

      <div class="section-title">样式设置</div>
      <el-form-item label="聊天区高度">
        <el-slider v-model="configObj.heightConfig.val" :min="configObj.heightConfig.min" :max="configObj.heightConfig.max" />
      </el-form-item>
      <el-form-item label="背景颜色">
        <el-color-picker v-model="configObj.bgColor" />
      </el-form-item>
      <el-form-item label="主题颜色">
        <el-color-picker v-model="configObj.themeColor" />
      </el-form-item>
      <el-form-item label="背景圆角">
        <el-slider v-model="configObj.fillet.val" :min="configObj.fillet.min" :max="configObj.fillet.max" />
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
import { getAiAgentListApi } from '@/api/crud/aiAgent';

export default {
  name: 'c_ai_chat',
  componentsName: 'z_ai_chat',
  cname: 'AI聊天',
  props: {
    activeIndex: { type: null },
    num: { type: null },
    index: { type: null },
  },
  data() {
    return {
      configObj: {},
      agentList: [],
    };
  },
  computed: {
    showTitle: {
      get() {
        return this.configObj.showTitle ? Number(this.configObj.showTitle.val) : 1;
      },
      set(val) {
        this.$set(this.configObj.showTitle, 'val', val);
      },
    },
  },
  watch: {
    num(nVal) {
      this.initConfig(nVal);
    },
    configObj: {
      handler(nVal) {
        this.$store.commit('mobildConfig/UPDATEARR', { num: this.num, val: nVal });
      },
      deep: true,
    },
  },
  mounted() {
    this.initConfig(this.num);
    this.getAgentList();
  },
  methods: {
    initConfig(num) {
      const storeConfig = this.$store.state.mobildConfig.defaultArray[num];
      if (!storeConfig) return;
      this.configObj = JSON.parse(JSON.stringify(storeConfig));
      if (!this.configObj.agentConfig) {
        this.$set(this.configObj, 'agentConfig', { title: '选择Agent', value: '' });
      }
      if (!this.configObj.titleConfig) {
        this.$set(this.configObj, 'titleConfig', { title: '组件标题', value: 'AI智能助手', max: 20 });
      }
      if (!this.configObj.welcomeConfig) {
        this.$set(this.configObj, 'welcomeConfig', {
          title: '欢迎语',
          value: '您好，我是AI智能助手，很高兴为您服务。',
          max: 100,
        });
      }
      if (!this.configObj.tipConfig) {
        this.$set(this.configObj, 'tipConfig', { title: '输入框提示', value: '请输入您的问题…', max: 30 });
      }
      if (!this.configObj.heightConfig) {
        this.$set(this.configObj, 'heightConfig', { title: '聊天区高度', val: 420, min: 240, max: 800 });
      }
      if (!this.configObj.showTitle) {
        this.$set(this.configObj, 'showTitle', { title: '显示标题', val: 1 });
      }
      if (!this.configObj.fillet) {
        this.$set(this.configObj, 'fillet', { title: '背景圆角', val: 12, min: 0, max: 40 });
      }
      if (!this.configObj.bgColor) this.$set(this.configObj, 'bgColor', '#F5F7FA');
      if (!this.configObj.themeColor) this.$set(this.configObj, 'themeColor', '#409EFF');
    },
    getAgentList() {
      getAiAgentListApi({ page: 1, limit: 100 })
        .then((res) => {
          const list = (res.data && res.data.list) || [];
          this.agentList = list.filter((item) => Number(item.status) === 1);
        })
        .catch(() => {
          this.agentList = [];
        });
    },
    onAgentChange(val) {
      const agent = this.agentList.find((item) => String(item.id) === String(val));
      if (agent) {
        this.$set(this.configObj, 'agentName', agent.name);
      }
    },
  },
};
</script>

<style scoped>
.ai-chat-config {
  padding: 4px 2px 20px;
}
.section-title {
  margin: 6px 0 12px;
  padding-left: 8px;
  border-left: 3px solid var(--prev-color-primary, #409eff);
  font-size: 13px;
  font-weight: 600;
  color: #303133;
}
.tips {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.5;
  color: #909399;
}
</style>
