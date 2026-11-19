<template>
  <view class="ai-chat-comp" :style="wrapStyle">
    <view v-if="showTitle" class="ai-chat-title">{{ title }}</view>
    <view class="ai-chat-menu" @tap="toggleMenu">⋯</view>
    <view v-if="panel === 'menu'" class="ai-chat-menu-mask" @tap="closePanel"></view>
    <view v-if="panel === 'menu'" class="ai-chat-menu-list">
      <view class="ai-chat-menu-item" @tap="newChat">新建聊天</view>
      <view class="ai-chat-menu-item" @tap="openHistory">查看历史记录</view>
      <view class="ai-chat-menu-item" @tap="openAgents">选择 Agent</view>
    </view>
    <scroll-view
      class="ai-chat-body"
      :style="{ flex: '1 1 auto', minHeight: '0' }"
      scroll-y
      :scroll-into-view="scrollId"
      scroll-with-animation
    >
      <view class="ai-chat-row">
        <view class="ai-avatar" :style="{ background: themeColor }">{{ avatarText }}</view>
        <view class="ai-bubble">{{ welcome }}</view>
      </view>
      <view
        v-for="(item, index) in chat"
        :key="index"
        :id="'ai-msg-' + index"
        class="ai-chat-row"
        :class="{ right: item.role === 'user' }"
      >
        <view v-if="item.role === 'assistant'" class="ai-avatar" :style="{ background: themeColor }">{{ avatarText }}</view>
        <view class="ai-bubble" :class="{ user: item.role === 'user' }" :style="item.role === 'user' ? { background: themeColor } : {}">
          <rich-text v-if="item.role === 'assistant'" :nodes="item.html || item.content"></rich-text>
          <text v-else>{{ item.content }}</text>
        </view>
        <view v-if="item.role === 'user'" class="ai-avatar me">我</view>
      </view>
      <view v-if="sending" class="ai-chat-row">
        <view class="ai-avatar" :style="{ background: themeColor }">{{ avatarText }}</view>
        <view class="ai-bubble loading">正在思考…</view>
      </view>
      <view id="ai-msg-bottom" style="height: 1px"></view>
    </scroll-view>
    <view class="ai-chat-foot">
      <view class="ai-chat-input">
        <input v-model="input" :placeholder="tip" confirm-type="send" :maxlength="500" @confirm="send" />
      </view>
      <view class="ai-send" :style="{ background: sending ? '#a0cfff' : themeColor }" @tap="send">发送</view>
    </view>
    <view v-if="panel === 'history' || panel === 'agents'" class="ai-chat-panel">
      <view class="ai-chat-panel-head">
        <text class="ai-chat-panel-title">{{ panel === 'history' ? '聊天记录' : '选择 Agent' }}</text>
        <text class="ai-chat-panel-close" @tap="closePanel">×</text>
      </view>
      <scroll-view class="ai-chat-panel-body" scroll-y>
        <view v-if="listLoading" class="ai-chat-empty">加载中…</view>
        <template v-else-if="panel === 'history'">
          <view v-if="renameId" class="ai-chat-rename">
            <input v-model="renameText" class="ai-chat-rename-input" placeholder="页面标题" maxlength="20" />
            <text class="ai-chat-rename-btn" @tap="cancelRename">取消</text>
            <text class="ai-chat-rename-btn primary" @tap="confirmRename">保存</text>
          </view>
          <view v-if="!sessions.length" class="ai-chat-empty">暂无聊天记录</view>
          <view v-for="s in sessions" :key="s.id" class="ai-chat-list-item row" @tap="openSession(s)">
            <view class="ai-chat-list-main">
              <view class="ai-chat-list-title">{{ s.title || '新会话' }}</view>
              <view class="ai-chat-list-time">{{ s.time }}</view>
            </view>
            <text class="ai-chat-list-edit" @tap.stop="startRename(s)">✎</text>
            <text class="ai-chat-list-del" @tap.stop="delSession(s)">×</text>
          </view>
        </template>
        <template v-else>
          <view v-if="!agents.length" class="ai-chat-empty">暂无可用 Agent</view>
          <view v-for="a in agents" :key="a.id" class="ai-chat-list-item row" @tap="pickAgent(a)">
            <text :class="{ active: a.id === agentId }">{{ a.name }}</text>
            <text v-if="a.id === agentId" class="ai-chat-current">当前</text>
          </view>
        </template>
      </scroll-view>
    </view>
  </view>
</template>

<script>
import { TOKENNAME, HTTP_REQUEST_URL } from '@/config/app.js';

export default {
  name: 'aiChat',
  props: {
    dataConfig: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      input: '',
      sending: false,
      chat: [],
      sessionId: 0,
      agentId: 0,
      agentName: '',
      scrollId: '',
      panel: '',
      sessions: [],
      agents: [],
      listLoading: false,
      fillH: 0,
      currentId: '',
      renameId: '',
      renameText: '',
    };
  },
  computed: {
    conf() {
      return this.dataConfig || {};
    },
    showTitle() {
      return !this.conf.showTitle || Number(this.conf.showTitle.val) !== 0;
    },
    title() {
      return (this.conf.titleConfig && this.conf.titleConfig.value) || 'AI智能助手';
    },
    welcome() {
      return (this.conf.welcomeConfig && this.conf.welcomeConfig.value) || '您好，我是AI智能助手，很高兴为您服务。';
    },
    tip() {
      return (this.conf.tipConfig && this.conf.tipConfig.value) || '请输入您的问题…';
    },
    bodyHeight() {
      return Number(this.conf.heightConfig && this.conf.heightConfig.val) || 420;
    },
    bgColor() {
      return this.conf.bgColor || '#F5F7FA';
    },
    themeColor() {
      return this.conf.themeColor || '#409EFF';
    },
    radius() {
      return this.conf.fillet ? Number(this.conf.fillet.val) || 0 : 12;
    },
    avatarText() {
      const name = (this.agentName || this.conf.agentName || '').trim();
      return name ? name.slice(0, 2) : 'AI';
    },
    wrapStyle() {
      const style = {
        background: this.bgColor,
        borderRadius: this.radius + 'px',
        display: 'flex',
        flexDirection: 'column',
        overflow: 'hidden',
      };
      if (this.fillH) {
        style.height = this.fillH + 'px';
      } else {
        style.minHeight = '280px';
      }
      return style;
    },
  },
  created() {
    const agent = this.conf.agentConfig || {};
    this.agentId = parseInt(agent.value, 10) || 0;
    this.agentName = this.conf.agentName || '';
    if (this.agentId) {
      this.loadAgent();
    }
  },
  mounted() {
    this.calcFill();
    this.onResize = () => this.calcFill();
    uni.onWindowResize && uni.onWindowResize(this.onResize);
  },
  beforeDestroy() {
    if (this.onResize && uni.offWindowResize) {
      uni.offWindowResize(this.onResize);
    }
  },
  methods: {
    token() {
      return this.$store && this.$store.state && this.$store.state.app ? this.$store.state.app.token : '';
    },
    request(url, method, data) {
      return new Promise((resolve, reject) => {
        const header = { 'Content-Type': 'application/json' };
        const token = this.token();
        if (token) {
          header[TOKENNAME] = 'Bearer ' + token;
        }
        uni.request({
          url: HTTP_REQUEST_URL + url,
          method,
          data,
          header,
          success: (res) => {
            const body = res.data || {};
            if (body.status === 200) {
              resolve(body.data || {});
            } else {
              reject(new Error(body.msg || '请求失败'));
            }
          },
          fail: () => reject(new Error('网络异常，请稍后重试')),
        });
      });
    },
    loadAgent() {
      this.request('/api/ai/agent?agent_id=' + this.agentId, 'GET')
        .then((data) => {
          if (data && data.name) {
            this.agentName = data.name;
          }
        })
        .catch(() => {});
    },
    calcFill() {
      this.$nextTick(() => {
        const query = uni.createSelectorQuery().in(this);
        query
          .select('.ai-chat-comp')
          .boundingClientRect((rect) => {
            if (!rect) return;
            const sys = uni.getSystemInfoSync();
            let h = (sys.windowHeight || sys.screenHeight || 667) - rect.top;
            if (h < 240) h = 240;
            if (h > 900) h = 900;
            this.fillH = Math.round(h);
          })
          .exec();
      });
    },
    readStore() {
      try {
        const list = uni.getStorageSync('zzai_sessions_v1');
        return Array.isArray(list) ? list : [];
      } catch (e) {
        return [];
      }
    },
    writeStore(list) {
      try {
        uni.setStorageSync('zzai_sessions_v1', list);
      } catch (e) {}
    },
    titleOf(messages) {
      for (let i = 0; i < messages.length; i++) {
        const m = messages[i];
        if (m.role === 'user' && m.content) {
          const text = String(m.content).replace(/\s+/g, ' ').trim();
          return text.length > 22 ? text.slice(0, 22) + '…' : text;
        }
      }
      return '新会话';
    },
    timeOf(ts) {
      const d = new Date(ts || Date.now());
      const pad = (n) => (n < 10 ? '0' + n : '' + n);
      const now = new Date();
      const sameDay =
        d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate();
      return sameDay
        ? pad(d.getHours()) + ':' + pad(d.getMinutes())
        : d.getMonth() + 1 + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    },
    saveLocal() {
      const messages = this.chat
        .filter((m) => m.role === 'user' || m.role === 'assistant')
        .map((m) => ({ role: m.role, content: m.content, html: m.html || '' }));
      if (!messages.length) return;
      if (!this.currentId) this.currentId = 's' + Date.now();
      const old = this.findRecord(this.currentId);
      const title = old && old.custom && old.title ? old.title : this.titleOf(messages);
      let list = this.readStore().filter((item) => item.id !== this.currentId);
      list.unshift({
        id: this.currentId,
        agentId: this.agentId,
        agentName: this.agentName,
        sid: this.sessionId,
        title,
        custom: old && old.custom ? 1 : 0,
        time: Date.now(),
        messages,
      });
      if (list.length > 30) list = list.slice(0, 30);
      this.writeStore(list);
    },
    scrollBottom() {
      this.$nextTick(() => {
        this.scrollId = '';
        this.$nextTick(() => {
          this.scrollId = 'ai-msg-bottom';
        });
      });
    },
    toggleMenu() {
      this.panel = this.panel === 'menu' ? '' : 'menu';
    },
    closePanel() {
      this.panel = '';
      this.renameId = '';
      this.renameText = '';
    },
    newChat() {
      const current = this.findRecord(this.currentId);
      if (!current || (current.messages && current.messages.length)) {
        this.currentId = 's' + Date.now();
        const list = this.readStore();
        list.unshift({
          id: this.currentId,
          agentId: this.agentId,
          agentName: this.agentName,
          sid: 0,
          title: '新会话',
          custom: 0,
          time: Date.now(),
          messages: [],
        });
        this.writeStore(list.slice(0, 30));
      }
      this.chat = [];
      this.sessionId = 0;
      this.input = '';
      this.panel = '';
    },
    findRecord(id) {
      return this.readStore().find((item) => item.id === id) || null;
    },
    startRename(session) {
      this.renameId = session.id;
      this.renameText = session.title || '';
    },
    cancelRename() {
      this.renameId = '';
      this.renameText = '';
    },
    confirmRename() {
      const title = (this.renameText || '').trim();
      if (title) {
        const list = this.readStore();
        const record = list.find((item) => item.id === this.renameId);
        if (record) {
          record.title = title;
          record.custom = 1;
          this.writeStore(list);
        }
      }
      this.renameId = '';
      this.renameText = '';
      this.openHistory();
    },
    openHistory() {
      this.panel = 'history';
      this.listLoading = false;
      this.sessions = this.readStore()
        .slice(0)
        .sort((a, b) => (b.time || 0) - (a.time || 0))
        .map((item) => ({
          id: item.id,
          title: item.title || '新会话',
          time: this.timeOf(item.time),
          agentName: item.agentName || '',
        }));
    },
    openAgents() {
      this.panel = 'agents';
      if (this.agents.length) {
        this.listLoading = false;
        return;
      }
      this.listLoading = true;
      this.request('/api/ai/agents', 'GET')
        .then((data) => {
          this.agents = Array.isArray(data) ? data : [];
        })
        .catch(() => {})
        .then(() => {
          this.listLoading = false;
        });
    },
    openSession(session) {
      const record = this.readStore().find((item) => item.id === session.id);
      if (!record) return;
      this.currentId = record.id;
      this.agentId = record.agentId || this.agentId;
      this.agentName = record.agentName || this.agentName;
      this.sessionId = record.sid || 0;
      this.chat = (record.messages || []).map((item) => ({
        role: item.role,
        content: item.content,
        html: item.html || '',
      }));
      this.panel = '';
      this.scrollBottom();
    },
    delSession(session) {
      this.writeStore(this.readStore().filter((item) => item.id !== session.id));
      if (this.currentId === session.id) {
        this.currentId = '';
        this.chat = [];
        this.sessionId = 0;
      }
      this.openHistory();
    },
    pickAgent(agent) {
      if (!agent) return;
      this.agentId = agent.id;
      this.agentName = agent.name;
      this.chat = [];
      this.sessionId = 0;
      this.currentId = '';
      this.panel = '';
    },
    send() {
      if (this.sending) return;
      const text = (this.input || '').trim();
      if (!text) return;
      if (!this.agentId) {
        uni.showToast({ title: '管理员暂未配置AI助手', icon: 'none' });
        return;
      }
      this.input = '';
      if (!this.currentId) this.currentId = 's' + Date.now();
      this.chat.push({ role: 'user', content: text, html: '' });
      this.sending = true;
      this.scrollBottom();
      this.saveLocal();

      const messages = this.chat
        .filter((item) => item.role === 'user' || item.role === 'assistant')
        .map((item) => ({ role: item.role, content: item.content }));

      this.request('/api/ai/chat', 'POST', {
        agent_id: this.agentId,
        message: text,
        session_id: this.sessionId,
        messages,
      })
        .then((data) => {
          this.sessionId = data.session_id || this.sessionId;
          this.chat.push({ role: 'assistant', content: data.content || '', html: data.html || '' });
        })
        .catch((err) => {
          this.chat.push({ role: 'assistant', content: '', html: '<p style="color:#f56c6c;margin:0">' + err.message + '</p>' });
        })
        .then(() => {
          this.sending = false;
          this.saveLocal();
          this.scrollBottom();
        });
    },
  },
};
</script>

<style scoped>
.ai-chat-comp {
  position: relative;
  padding: 12px;
  box-sizing: border-box;
}
.ai-chat-menu {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 28px;
  height: 28px;
  line-height: 20px;
  text-align: center;
  font-size: 20px;
  color: #909399;
  z-index: 6;
}
.ai-chat-menu-mask {
  position: fixed;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  z-index: 5;
}
.ai-chat-menu-list {
  position: absolute;
  top: 38px;
  right: 8px;
  z-index: 8;
  min-width: 128px;
  padding: 4px 0;
  background: #fff;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}
.ai-chat-menu-item {
  padding: 9px 14px;
  font-size: 13px;
  color: #303133;
  white-space: nowrap;
}
.ai-chat-panel {
  position: absolute;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  z-index: 9;
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
}
.ai-chat-panel-head {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid #ebeef5;
  flex: 0 0 auto;
}
.ai-chat-panel-title {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}
.ai-chat-panel-close {
  width: 26px;
  height: 26px;
  line-height: 22px;
  text-align: center;
  font-size: 18px;
  color: #909399;
}
.ai-chat-panel-body {
  flex: 1;
  height: 100%;
}
.ai-chat-empty {
  padding: 20px;
  text-align: center;
  font-size: 12px;
  color: #909399;
}
.ai-chat-list-item {
  padding: 10px 12px;
  border-bottom: 1px solid #f2f3f5;
}
.ai-chat-list-main {
  flex: 1;
  min-width: 0;
}
.ai-chat-rename {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  border-bottom: 1px solid #ebeef5;
  background: #fafafa;
}
.ai-chat-rename-input {
  flex: 1;
  min-width: 0;
  height: 28px;
  line-height: 28px;
  padding: 0 8px;
  font-size: 12px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fff;
}
.ai-chat-rename-btn {
  margin-left: 8px;
  font-size: 12px;
  color: #909399;
}
.ai-chat-rename-btn.primary {
  color: #409eff;
}
.ai-chat-list-edit {
  flex: 0 0 auto;
  width: 26px;
  text-align: center;
  font-size: 14px;
  color: #909399;
}
.ai-chat-list-del {
  flex: 0 0 auto;
  width: 28px;
  text-align: center;
  font-size: 16px;
  color: #c0c4cc;
}
.ai-chat-list-item.row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.ai-chat-list-title {
  font-size: 13px;
  color: #303133;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ai-chat-list-time {
  margin-top: 2px;
  font-size: 11px;
  color: #909399;
}
.ai-chat-list-item .active {
  color: #409eff;
  font-weight: 600;
}
.ai-chat-current {
  font-size: 11px;
  color: #409eff;
}
.ai-chat-title {
  padding: 4px 0 10px;
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  text-align: center;
}
.ai-chat-body {
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
  max-width: 74%;
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
.ai-bubble.loading {
  color: #909399;
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
