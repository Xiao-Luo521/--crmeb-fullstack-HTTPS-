<template>
  <div>
    <el-card shadow="never" class="ivu-mt" :body-style="{padding:0}">
        <div class="padding-add">
           <el-form
             ref="curlFrom"
             :model="from"
             :label-width="labelWidth"
             :label-position="labelPosition"
             inline
             @submit.native.prevent
           >
        <el-form-item label="模型名称：" label-for="name">
            <el-input
                v-model="from.name"
                placeholder="请输入模型名称"
                class="form_content_width"
            />
        </el-form-item>

        <el-form-item label="模型类型：" label-for="type">
            <el-select
                v-model="from.type"
                placeholder="请选择"
                clearable
                @change="searchs"
                class="form_content_width"
            >
        <el-option value="1" label="聊天模型"></el-option>
        <el-option value="2" label="多模态模型"></el-option>
        <el-option value="3" label="嵌入式模型"></el-option>
            </el-select>
        </el-form-item>

        <el-form-item label="模型标识：" label-for="mode">
            <el-input
                v-model="from.mode"
                placeholder="请输入模型标识"
                class="form_content_width"
            />
        </el-form-item>

        <el-form-item label="状态：" label-for="statue">
            <el-input
                v-model="from.statue"
                placeholder="请输入状态"
                class="form_content_width"
            />
        </el-form-item>

              <el-form-item>
                 <el-button type="primary" @click="searchs">查询</el-button>
              </el-form-item>
           </el-form>
        </div>
    </el-card>

    <el-card shadow="never" dis-hover class="ivu-mt mt16">
      <el-row type="flex">
        <el-col v-bind="grid">
          <el-button v-auth="['ai_model-add']" type="primary" icon="md-add" @click="add">添加</el-button>
        </el-col>
      </el-row>
      <el-table
          :data="dataList"
          ref="table"
          class="mt25"
          :loading="loading"
          highlight-current-row
      >
        <el-table-column prop="name" label="模型名称">
        </el-table-column>
        <el-table-column prop="type_label" label="模型类型">
        </el-table-column>
        <el-table-column prop="mode" label="模型标识">
        </el-table-column>
        <el-table-column prop="base_url" label="接口地址">
        </el-table-column>
        <el-table-column prop="api_key" label="API密钥">
        </el-table-column>
        <el-table-column label="操作">
            <template slot-scope="scope">
              <a @click="testModel(scope.row)">测试</a>
              <el-divider direction="vertical" />
              <a @click="show(scope.row)">详情</a>
              <el-divider direction="vertical" />
              <a @click="edit(scope.row.id)">修改</a>
              <el-divider direction="vertical" />
              <a @click="del(scope.row, '删除', scope.$index)">删除</a>
            </template>
        </el-table-column>
      </el-table>
      <div class="acea-row row-right page">
        <pagination :total="total"  @pagination="pageChange"  :limit.sync="from.limit" :page.sync="from.page" />
      </div>
    </el-card>

    <el-dialog title="查看详情" :visible.sync="dialogTableVisible" v-if='dialogTableVisible'>
        <el-descriptions title="ai_model">
            <el-descriptions-item label="自增ID">{{info.id}}</el-descriptions-item>
            <el-descriptions-item label="模型名称">{{info.name}}</el-descriptions-item>
            <el-descriptions-item label="模型类型">{{info.type_label}}</el-descriptions-item>
            <el-descriptions-item label="模型标识">{{info.mode}}</el-descriptions-item>
            <el-descriptions-item label="接口地址">{{info.base_url}}</el-descriptions-item>
            <el-descriptions-item label="API密钥">{{info.api_key}}</el-descriptions-item>
            <el-descriptions-item label="状态">{{info.statue}}</el-descriptions-item>
            <el-descriptions-item label="备足">{{info.remark}}</el-descriptions-item>
            <el-descriptions-item label="添加时间">{{info.create_time}}</el-descriptions-item>
            <el-descriptions-item label="修改时间">{{info.update_time}}</el-descriptions-item>

        </el-descriptions>
    </el-dialog>

    <el-dialog title="模型测试" :visible.sync="testVisible" width="600px" @closed="resetTest">
      <el-form label-width="90px" @submit.native.prevent>
        <el-form-item label="当前模型">
          <span>{{ testInfo.name }}（{{ testInfo.mode }}）</span>
        </el-form-item>
        <el-form-item label="发送内容">
          <el-input
            v-model="testContent"
            type="textarea"
            :rows="3"
            placeholder="默认发送：你好"
          />
        </el-form-item>
      </el-form>
      <div v-if="testLoading" class="test-result loading">正在请求模型，请稍候…</div>
      <div v-else-if="testError" class="test-result error">测试失败：{{ testError }}</div>
      <div v-else-if="testReply" class="test-result success">
        <div class="test-label">模型回复（测试通过）</div>
        <div class="test-reply">{{ testReply }}</div>
        <div v-if="testUsage && testUsage.total_tokens" class="test-usage">
          消耗 tokens：{{ testUsage.total_tokens }}
        </div>
      </div>
      <span slot="footer">
        <el-button @click="testVisible = false">关闭</el-button>
        <el-button type="primary" :loading="testLoading" @click="sendTest">发送测试</el-button>
      </span>
    </el-dialog>

  </div>
</template>

<script>
import { mapState } from 'vuex';
import { aiModelSaveApi, aiModelStatusApi, aiModelDeleteApi, aiModelUpdateApi, getAiModelCreateApi, getAiModelEditApi, getAiModelListApi, getAiModelReadApi, aiModelTestApi} from '@/api/crud/aiModel';
export default {
  name: 'ai_model',
  data() {
    return {
      grid: {
        xl: 7,
        lg: 7,
        md: 12,
        sm: 24,
        xs: 24,
      },
      loading: false,
      from: {
        name:'',
        type:'',
        mode:'',
        statue:'',
        page: 1,
        limit: 15,
      },
      dataList: [],
      total: 0,
      dialogTableVisible: false,
      info: {},
      testVisible: false,
      testLoading: false,
      testInfo: {},
      testContent: '你好',
      testReply: '',
      testError: '',
      testUsage: {},
    };
  },
  computed: {
    ...mapState('media', ['isMobile']),
    labelWidth() {
      return this.isMobile ? undefined : '75px';
    },
    labelPosition() {
      return this.isMobile ? 'top' : 'left';
    },
  },
  created() {
    this.getList();
  },
  methods: {
    show(row) {
        getAiModelReadApi(row.id).then(res => {
           this.dialogTableVisible = true;
           this.info = res.data;
        }).catch(res => {
          this.$message.error(res.msg);
        })
    },
    // 打开测试弹窗
    testModel(row) {
      this.testInfo = { id: row.id, name: row.name, mode: row.mode };
      this.testContent = '你好';
      this.testReply = '';
      this.testError = '';
      this.testUsage = {};
      this.testVisible = true;
    },
    // 关闭测试弹窗时复位
    resetTest() {
      this.testLoading = false;
      this.testReply = '';
      this.testError = '';
      this.testUsage = {};
    },
    // 发送测试消息
    sendTest() {
      const content = (this.testContent || '').trim() || '你好';
      this.testLoading = true;
      this.testReply = '';
      this.testError = '';
      this.testUsage = {};
      aiModelTestApi(this.testInfo.id, { content })
        .then((res) => {
          this.testReply = res.data.reply;
          this.testUsage = res.data.usage || {};
        })
        .catch((err) => {
          this.testError = err.msg || '请求失败';
        })
        .finally(() => {
          this.testLoading = false;
        });
    },
    //修改状态
    updateStatus(row, field) {
        aiModelStatusApi(row.id, {field: field, value: row[field]})
        .then(async (res) => {
          this.$message.success(res.msg);
        })
        .catch((res) => {
          this.$message.error(res.msg);
        });
    },
    // 添加
    add() {
      this.$modalForm(getAiModelCreateApi()).then(() => this.getList());
    },
    // 表格搜索
    searchs() {
      this.from.page = 1;
      this.getList();
    },
    //列表
    getList() {
      this.loading = true;
      getAiModelListApi(this.from)
          .then(async (res) => {
            let data = res.data;
            this.dataList = data.list;
            this.total = data.count;
            this.loading = false;
          })
          .catch((res) => {
            this.loading = false;
            this.$message.error(res.msg);
          });
    },
    //分页
    pageChange(index) {
      this.from.page = index;
      this.getList();
    },
    // 修改
    edit(id) {
      this.$modalForm(getAiModelEditApi(id)).then(() => this.getList());
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `crud/ai_model/${row.id}`,
        method: 'DELETE',
        ids: '',
      };
      this.$modalSure(delfromData)
          .then((res) => {
            this.$message.success(res.msg);
            this.getList();
          })
          .catch((res) => {
            this.$message.error(res.msg);
          });
    },
  },
};
</script>

<style scoped>
.test-result {
  padding: 12px 16px;
  border-radius: 4px;
  background: #f5f7fa;
  line-height: 22px;
}
.test-result.loading {
  color: #909399;
}
.test-result.error {
  color: #f56c6c;
  background: #fef0f0;
}
.test-result.success {
  color: #67c23a;
  background: #f0f9eb;
}
.test-result .test-label {
  margin-bottom: 6px;
  font-weight: 600;
}
.test-result .test-reply {
  color: #303133;
  white-space: pre-wrap;
  word-break: break-all;
}
.test-result .test-usage {
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
}
</style>
