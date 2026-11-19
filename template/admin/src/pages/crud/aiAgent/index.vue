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
        <el-form-item label="agent名称：" label-for="name">
            <el-input
                v-model="from.name"
                placeholder="请输入agent名称"
                class="form_content_width"
            />
        </el-form-item>

        <el-form-item label="聊天模型：" label-for="model_id">
            <el-select
                v-model="from.model_id"
                placeholder="请选择聊天模型"
                clearable
                filterable
                class="form_content_width"
            >
              <el-option
                  v-for="item in models"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
              />
            </el-select>
        </el-form-item>

        <el-form-item label="状态：" label-for="status">
            <el-input
                v-model="from.status"
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
          <el-button v-auth="['ai_agent-add']" type="primary" icon="md-add" @click="add">添加</el-button>
        </el-col>
      </el-row>
      <el-table
          :data="dataList"
          ref="table"
          class="mt25"
          :loading="loading"
          highlight-current-row
      >
        <el-table-column prop="name" label="Agent名称">
        </el-table-column>
        <el-table-column prop="model_name" label="聊天模型">
        </el-table-column>
        <el-table-column prop="prompt" label="系统提示词" show-overflow-tooltip>
        </el-table-column>
        <el-table-column prop="temperature" label="温度" width="80">
        </el-table-column>
        <el-table-column label="绑定知识库">
            <template slot-scope="scope">
              <span v-if="scope.row.file_names">{{ scope.row.file_names }}</span>
              <span v-else style="color:#999">未绑定</span>
            </template>
        </el-table-column>
        <el-table-column label="状态">
            <template slot-scope="scope">
                <el-switch  :active-value="1" :inactive-value="0" v-model="scope.row.status" :value="scope.row.status" size="large" @change="updateStatus(scope.row,'status')" />
            </template>
        </el-table-column>
        <el-table-column label="操作">
            <template slot-scope="scope">
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
        <el-descriptions title="ai_agent">
            <el-descriptions-item label="自增ID">{{info.id}}</el-descriptions-item>
            <el-descriptions-item label="Agent名称">{{info.name}}</el-descriptions-item>
            <el-descriptions-item label="聊天模型">{{info.model_name}}</el-descriptions-item>
            <el-descriptions-item label="系统提示词">{{info.prompt}}</el-descriptions-item>
            <el-descriptions-item label="温度">{{info.temperature}}</el-descriptions-item>
            <el-descriptions-item label="绑定知识库">{{info.file_names || '未绑定'}}</el-descriptions-item>
            <el-descriptions-item label="状态">{{info.status}}</el-descriptions-item>

        </el-descriptions>
    </el-dialog>

  </div>
</template>

<script>
import { mapState } from 'vuex';
import { aiAgentSaveApi, aiAgentStatusApi, aiAgentDeleteApi, aiAgentUpdateApi, getAiAgentCreateApi, getAiAgentEditApi, getAiAgentListApi, getAiAgentReadApi, getAiAgentOptionsApi} from '@/api/crud/aiAgent';
export default {
  name: 'ai_agent',
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
        model_id:'',
        status:'',
        page: 1,
        limit: 15,
      },
      dataList: [],
      total: 0,
      dialogTableVisible: false,
      info: {},
      models: [],
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
    this.getOptions();
    this.getList();
  },
  methods: {
    // 下拉选项
    getOptions() {
      getAiAgentOptionsApi().then((res) => {
        this.models = res.data.models || [];
      }).catch((res) => {
        this.$message.error(res.msg);
      });
    },
    show(row) {
        getAiAgentReadApi(row.id).then(res => {
           this.dialogTableVisible = true;
           this.info = res.data;
        }).catch(res => {
          this.$message.error(res.msg);
        })
    },
    //修改状态
    updateStatus(row, field) {
        aiAgentStatusApi(row.id, {field: field, value: row[field]})
        .then(async (res) => {
          this.$message.success(res.msg);
        })
        .catch((res) => {
          this.$message.error(res.msg);
        });
    },
    // 添加
    add() {
      this.$modalForm(getAiAgentCreateApi()).then(() => this.getList());
    },
    // 表格搜索
    searchs() {
      this.from.page = 1;
      this.getList();
    },
    //列表
    getList() {
      this.loading = true;
      getAiAgentListApi(this.from)
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
      this.$modalForm(getAiAgentEditApi(id)).then(() => this.getList());
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `crud/ai_agent/${row.id}`,
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

<style scoped lang="stylus"></style>
