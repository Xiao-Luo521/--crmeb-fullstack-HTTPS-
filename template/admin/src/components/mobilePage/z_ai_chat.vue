<template>
  <div class="ai-chat-preview" :style="{ background: bgColor, borderRadius: radius + 'px' }">
    <div v-if="showTitle" class="ai-chat-title">{{ title }}</div>
    <div class="ai-chat-menu" @click.stop="menuOpen = !menuOpen">⋯</div>
    <div v-if="menuOpen" class="ai-chat-menu-list">
      <div class="ai-chat-menu-item" @click.stop="closeMenu">新建聊天</div>
      <div class="ai-chat-menu-item" @click.stop="closeMenu">查看历史记录</div>
      <div class="ai-chat-menu-item" @click.stop="closeMenu">选择 Agent</div>
    </div>
    <div class="ai-chat-body" :style="{ minHeight: '280px', maxHeight: 'min(' + height + 'px, 62vh)' }">
      <div class="ai-chat-row">
        <div class="ai-avatar" :style="{ background: themeColor }">{{ avatarText }}</div>
        <div class="ai-bubble">{{ welcome }}</div>
      </div>
      <div class="ai-chat-row right">
        <div class="ai-bubble user" :style="{ background: themeColor }">你好，可以简单介绍一下吗？</div>
        <div class="ai-avatar me">我</div>
      </div>
      <div class="ai-chat-row">
        <div class="ai-avatar" :style="{ background: themeColor }">{{ avatarText }}</div>
        <div class="ai-bubble">您好，很高兴为您服务，请问有什么可以帮您？</div>
      </div>
    </div>
    <div class="ai-chat-foot">
      <div class="ai-chat-input">
        <input :placeholder="tip" disabled />
      </div>
      <div class="ai-send" :style="{ background: themeColor }">发送</div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
  name: 'z_ai_chat',
  cname: 'AI聊天',
  icon: '#iconliaotian',
  configName: 'c_ai_chat',
  type: 2,
  defaultName: 'aiChat',
  props: {
    index: { type: null },
    num: { type: null },
    colorStyle: { type: null },
    configData: { type: Object, default: () => ({}) },
  },
  computed: {
    ...mapState('mobildConfig', ['defaultArray']),
    cfg() {
      return this.defaultArray[this.num] || this.defaultConfig;
    },
    agentConfig() {
      return this.cfg.agentConfig || {};
    },
    titleConfig() {
      return this.cfg.titleConfig || {};
    },
    welcomeConfig() {
      return this.cfg.welcomeConfig || {};
    },
    tipConfig() {
      return this.cfg.tipConfig || {};
    },
    heightConfig() {
      return this.cfg.heightConfig || {};
    },
    showTitle() {
      return !this.cfg.showTitle || Number(this.cfg.showTitle.val) !== 0;
    },
    title() {
      return this.titleConfig.value || 'AI智能助手';
    },
    welcome() {
      return this.welcomeConfig.value || '您好，我是AI智能助手，很高兴为您服务。';
    },
    tip() {
      return this.tipConfig.value || '请输入您的问题…';
    },
    height() {
      return Number(this.heightConfig.val) || 420;
    },
    bgColor() {
      return this.cfg.bgColor || '#F5F7FA';
    },
    themeColor() {
      return this.cfg.themeColor || '#409EFF';
    },
    radius() {
      return this.cfg.fillet ? Number(this.cfg.fillet.val) || 0 : 12;
    },
    avatarText() {
      const name = (this.cfg.agentName || '').trim();
      return name ? name.slice(0, 2) : 'AI';
    },
  },
  data() {
    return {
      menuOpen: false,
      defaultConfig: {
        cname: 'AI聊天',
        name: 'aiChat',
        timestamp: this.num,
        isHide: false,
        agentConfig: {
          title: '选择Agent',
          value: '',
        },
        titleConfig: {
          title: '组件标题',
          value: 'AI智能助手',
          place: '请输入标题',
          max: 20,
        },
        welcomeConfig: {
          title: '欢迎语',
          value: '您好，我是AI智能助手，很高兴为您服务。',
          place: '请输入欢迎语',
          max: 100,
        },
        tipConfig: {
          title: '输入框提示',
          value: '请输入您的问题…',
          place: '请输入提示文字',
          max: 30,
        },
        heightConfig: {
          title: '聊天区高度',
          val: 420,
          min: 240,
          max: 800,
        },
        bgColor: '#F5F7FA',
        themeColor: '#409EFF',
        showTitle: {
          title: '显示标题',
          val: 1,
        },
        fillet: {
          title: '背景圆角',
          val: 12,
          min: 0,
          max: 40,
        },
      },
    };
  },
  methods: {
    closeMenu() {
      this.menuOpen = false;
    },
  },
};
</script>

<style scoped>
.ai-chat-preview {
  position: relative;
  padding: 12px;
  box-sizing: border-box;
}
.ai-chat-menu {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 24px;
  line-height: 20px;
  text-align: center;
  font-size: 18px;
  color: #909399;
  cursor: pointer;
  z-index: 2;
}
.ai-chat-menu-list {
  position: absolute;
  top: 34px;
  right: 8px;
  min-width: 120px;
  padding: 4px 0;
  background: #fff;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  z-index: 3;
}
.ai-chat-menu-item {
  padding: 9px 14px;
  font-size: 13px;
  color: #303133;
  cursor: pointer;
}
.ai-chat-menu-item:hover {
  background: #f5f7fa;
}
.ai-chat-title {
  padding: 4px 0 10px;
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  text-align: center;
}
.ai-chat-body {
  overflow-y: auto;
  padding: 2px;
  box-sizing: border-box;
}
.ai-chat-row {
  display: flex;
  align-items: flex-start;
  margin-bottom: 14px;
}
.ai-chat-row.right {
  justify-content: flex-end;
}
.ai-avatar {
  flex: 0 0 30px;
  width: 30px;
  height: 30px;
  line-height: 30px;
  text-align: center;
  border-radius: 50%;
  color: #fff;
  font-size: 12px;
  flex-shrink: 0;
  margin-right: 8px;
}
.ai-avatar.me {
  background: #909399;
  margin-right: 0;
  margin-left: 8px;
}
.ai-bubble {
  max-width: calc(100% - 84px);
  padding: 9px 12px;
  font-size: 13px;
  line-height: 1.6;
  word-break: break-word;
  border-radius: 4px 10px 10px 10px;
  background: #fff;
  color: #303133;
  border: 1px solid #ebeef5;
}
.ai-bubble.user {
  border-radius: 10px 4px 10px 10px;
  color: #fff;
  border: 0;
}
.ai-chat-foot {
  display: flex;
  align-items: center;
  padding-top: 10px;
}
.ai-chat-input {
  flex: 1;
  height: 36px;
  display: flex;
  align-items: center;
  padding: 0 14px;
  background: #fff;
  border: 1px solid #ebeef5;
  border-radius: 18px;
  overflow: hidden;
}
.ai-chat-input input {
  flex: 1;
  width: 100%;
  border: 0;
  outline: none;
  background: transparent;
  font-size: 13px;
  color: #303133;
}
.ai-send {
  flex: 0 0 auto;
  margin-left: 8px;
  padding: 0 16px;
  height: 36px;
  line-height: 36px;
  border-radius: 18px;
  color: #fff;
  font-size: 13px;
}
</style>
