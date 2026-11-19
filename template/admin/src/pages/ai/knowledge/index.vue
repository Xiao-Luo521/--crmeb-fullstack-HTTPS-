<template>
  <div class="kb-page">
    <el-card shadow="never" class="kb-side" :body-style="{ padding: '0' }">
      <div class="kb-side-head">
        <span class="kb-side-title">知识库分类</span>
        <el-button type="text" icon="md-add" @click="openCategoryDialog(0)">添加</el-button>
      </div>
      <div class="kb-tree-wrap">
        <el-tree
          ref="tree"
          :data="treeData"
          node-key="id"
          default-expand-all
          highlight-current
          :expand-on-click-node="false"
          @node-click="handleNodeClick"
        >
          <span class="kb-node" slot-scope="{ node, data }">
            <span class="kb-node-label" :title="data.name">{{ data.name }}</span>
            <span v-if="data.id" class="kb-node-count">{{ data.file_count }}</span>
            <span v-if="data.id" class="kb-node-ops">
              <a @click.stop="openCategoryDialog(data.id)">添加</a>
              <a @click.stop="editCategory(data)">编辑</a>
              <a @click.stop="deleteCategory(data)">删除</a>
            </span>
          </span>
        </el-tree>
      </div>
    </el-card>

    <el-card shadow="never" class="kb-main">
      <div class="kb-toolbar">
        <el-form inline @submit.native.prevent>
          <el-form-item label="文件名称：">
            <el-input v-model="where.name" placeholder="请输入文件名称" clearable class="kb-name-input" @keyup.enter.native="searchs" />
          </el-form-item>
          <el-form-item label="解析状态：">
            <el-select v-model="where.parse_status" clearable placeholder="全部" class="kb-status-select" @change="searchs">
              <el-option value="0" label="待解析"></el-option>
              <el-option value="1" label="解析中"></el-option>
              <el-option value="2" label="解析成功"></el-option>
              <el-option value="3" label="解析失败"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="searchs">查询</el-button>
          </el-form-item>
        </el-form>
        <div class="kb-toolbar-right">
          <el-button icon="md-search" @click="searchDialog = true">检索测试</el-button>
          <el-upload
            action=""
            :multiple="true"
            :show-file-list="false"
            :http-request="customUpload"
            :before-upload="beforeUpload"
            accept=".txt,.md,.markdown,.csv,.doc,.docx,.xls,.xlsx,.pdf"
          >
            <el-button type="primary" icon="md-cloud-upload" :loading="uploading">上传文件</el-button>
          </el-upload>
        </div>
      </div>
      <div class="kb-tip">
        支持 txt、markdown、word（doc/docx）、excel（xls/xlsx）、pdf、csv，上传后自动解析并分块，已配置嵌入式模型时自动生成向量。
      </div>

      <el-table :data="dataList" v-loading="loading" class="mt20">
        <el-table-column prop="name" label="文件名称" min-width="220" show-overflow-tooltip></el-table-column>
        <el-table-column prop="cat_name" label="分类" width="120">
          <template slot-scope="scope">{{ scope.row.cat_name || '未分类' }}</template>
        </el-table-column>
        <el-table-column prop="file_type" label="类型" width="80"></el-table-column>
        <el-table-column prop="file_size_label" label="大小" width="100"></el-table-column>
        <el-table-column prop="chunk_count" label="分块数" width="90"></el-table-column>
        <el-table-column label="解析状态" width="120">
          <template slot-scope="scope">
            <el-tooltip :content="scope.row.parse_msg" placement="top" :disabled="!scope.row.parse_msg">
              <el-tag :type="statusType(scope.row.parse_status)" size="small">{{ scope.row.parse_status_label }}</el-tag>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column label="向量" width="110">
          <template slot-scope="scope">
            <el-tag :type="scope.row.embed_status === 1 ? 'success' : scope.row.embed_status === 2 ? 'danger' : 'info'" size="small">
              {{ scope.row.embed_status_label }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="上传时间" width="160"></el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template slot-scope="scope">
            <a v-if="scope.row.parse_status === 2" @click="doParse(scope.row)">重新解析</a>
            <a v-else @click="doParse(scope.row)">解析</a>
            <el-divider direction="vertical" />
            <a @click="showChunks(scope.row)">分块</a>
            <el-divider direction="vertical" />
            <a @click="deleteFile(scope.row)">删除</a>
          </template>
        </el-table-column>
      </el-table>
      <div class="acea-row row-right page">
        <pagination :total="total" @pagination="pageChange" :limit.sync="where.limit" :page.sync="where.page" />
      </div>
    </el-card>

    <el-dialog :title="categoryForm.id ? '编辑分类' : '添加分类'" :visible.sync="categoryDialog" width="460px">
      <el-form :model="categoryForm" label-width="90px">
        <el-form-item label="上级分类">
          <span>{{ categoryForm.pidName || '顶级分类' }}</span>
        </el-form-item>
        <el-form-item label="分类名称">
          <el-input v-model="categoryForm.name" placeholder="请输入分类名称" maxlength="50" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="categoryForm.sort" :min="0" :max="9999" />
        </el-form-item>
      </el-form>
      <span slot="footer">
        <el-button @click="categoryDialog = false">取消</el-button>
        <el-button type="primary" :loading="categoryLoading" @click="saveCategory">确定</el-button>
      </span>
    </el-dialog>

    <el-dialog :title="`分块内容 - ${chunkFile.name || ''}`" :visible.sync="chunkDialog" width="820px">
      <div class="kb-chunk-list" v-loading="chunkLoading">
        <div class="kb-chunk-item" v-for="item in chunkList" :key="item.id">
          <div class="kb-chunk-head">
            <span>#{{ item.chunk_index + 1 }}</span>
            <span>{{ item.char_count }} 字</span>
            <span>{{ item.dim }} 维</span>
          </div>
          <div class="kb-chunk-content">{{ item.content }}</div>
        </div>
        <div v-if="!chunkList.length" class="kb-empty">暂无分块数据，请先解析文件</div>
      </div>
      <div class="acea-row row-right page">
        <pagination :total="chunkTotal" @pagination="chunkPageChange" :limit.sync="chunkWhere.limit" :page.sync="chunkWhere.page" />
      </div>
    </el-dialog>

    <el-dialog title="知识库检索测试" :visible.sync="searchDialog" width="820px">
      <el-alert
        v-if="!hasModel"
        type="warning"
        :closable="false"
        show-icon
        title="未配置嵌入式模型，当前使用关键词匹配检索。可到「AI - 模型」中添加类型为“嵌入式模型”的模型后重新解析文件以获得向量检索能力。"
        class="mb15"
      ></el-alert>
      <el-alert
        v-else
        type="success"
        :closable="false"
        show-icon
        :title="`当前向量模型：${modelName}`"
        class="mb15"
      ></el-alert>
      <div class="kb-search-bar">
        <el-input v-model="searchForm.question" placeholder="请输入要检索的问题" @keyup.enter.native="doSearch" />
        <el-input-number v-model="searchForm.limit" :min="1" :max="20" controls-position="right" class="kb-limit" />
        <el-button type="primary" :loading="searchLoading" @click="doSearch">检索</el-button>
      </div>
      <div v-if="searchResult" class="kb-search-result">
        <div class="kb-search-mode">
          检索方式：{{ searchResult.mode === 'vector' ? '向量语义检索' : '关键词匹配' }}，命中 {{ searchResult.list.length }} 条
        </div>
        <div class="kb-chunk-item" v-for="(item, index) in searchResult.list" :key="index">
          <div class="kb-chunk-head">
            <span>相关度 {{ item.score }}</span>
            <span>{{ item.file_name }}</span>
            <span>分块 #{{ item.chunk_index + 1 }}</span>
          </div>
          <div class="kb-chunk-content">{{ item.content }}</div>
        </div>
        <div v-if="!searchResult.list.length" class="kb-empty">未检索到匹配内容</div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import {
  getKnowledgeCategoryApi,
  knowledgeCategorySaveApi,
  knowledgeCategoryUpdateApi,
  knowledgeCategoryDeleteApi,
  getKnowledgeFileListApi,
  knowledgeFileUploadApi,
  knowledgeFileParseApi,
  getKnowledgeFileChunksApi,
  knowledgeFileDeleteApi,
  knowledgeSearchApi,
  getKnowledgeModelApi,
} from '@/api/ai_knowledge';

export default {
  name: 'ai_knowledge',
  data() {
    return {
      categoryTree: [],
      where: {
        cat_id: 0,
        name: '',
        parse_status: '',
        page: 1,
        limit: 15,
      },
      dataList: [],
      total: 0,
      loading: false,
      uploading: false,
      parsingId: 0,
      categoryDialog: false,
      categoryLoading: false,
      categoryForm: {
        id: 0,
        pid: 0,
        pidName: '',
        name: '',
        sort: 0,
      },
      chunkDialog: false,
      chunkLoading: false,
      chunkFile: {},
      chunkList: [],
      chunkTotal: 0,
      chunkWhere: {
        page: 1,
        limit: 10,
      },
      searchDialog: false,
      searchLoading: false,
      searchForm: {
        question: '',
        cat_id: 0,
        limit: 5,
      },
      searchResult: null,
      hasModel: false,
      modelName: '',
    };
  },
  computed: {
    treeData() {
      return [
        {
          id: 0,
          name: '全部文件',
          children: this.categoryTree,
        },
      ];
    },
  },
  created() {
    this.getCategory();
    this.getList();
    this.getModelStatus();
  },
  methods: {
    statusType(status) {
      return { 0: 'info', 1: 'warning', 2: 'success', 3: 'danger' }[status] || 'info';
    },
    getCategory() {
      getKnowledgeCategoryApi()
        .then((res) => {
          this.categoryTree = res.data || [];
        })
        .catch((res) => {
          this.$message.error(res.msg);
        });
    },
    getModelStatus() {
      getKnowledgeModelApi()
        .then((res) => {
          this.hasModel = res.data.has_model;
          this.modelName = res.data.model && res.data.model.name ? res.data.model.name : '';
        })
        .catch(() => {});
    },
    getList() {
      this.loading = true;
      getKnowledgeFileListApi(this.where)
        .then((res) => {
          this.dataList = res.data.list;
          this.total = res.data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$message.error(res.msg);
        });
    },
    searchs() {
      this.where.page = 1;
      this.getList();
    },
    pageChange(index) {
      this.where.page = index;
      this.getList();
    },
    handleNodeClick(data) {
      this.where.cat_id = data.id || 0;
      this.searchs();
    },
    openCategoryDialog(pid) {
      this.categoryForm = {
        id: 0,
        pid: pid || 0,
        pidName: this.findNodeName(pid),
        name: '',
        sort: 0,
      };
      this.categoryDialog = true;
    },
    findNodeName(id) {
      if (!id) return '';
      let name = '';
      const walk = (list) => {
        list.forEach((item) => {
          if (item.id === id) name = item.name;
          if (item.children) walk(item.children);
        });
      };
      walk(this.categoryTree);
      return name;
    },
    editCategory(data) {
      this.categoryForm = {
        id: data.id,
        pid: data.pid,
        pidName: this.findNodeName(data.pid),
        name: data.name,
        sort: data.sort,
      };
      this.categoryDialog = true;
    },
    saveCategory() {
      if (!this.categoryForm.name) {
        return this.$message.warning('请输入分类名称');
      }
      this.categoryLoading = true;
      const data = {
        pid: this.categoryForm.pid,
        name: this.categoryForm.name,
        sort: this.categoryForm.sort,
      };
      const req = this.categoryForm.id
        ? knowledgeCategoryUpdateApi(this.categoryForm.id, data)
        : knowledgeCategorySaveApi(data);
      req
        .then((res) => {
          this.categoryLoading = false;
          this.categoryDialog = false;
          this.$message.success(res.msg);
          this.getCategory();
        })
        .catch((res) => {
          this.categoryLoading = false;
          this.$message.error(res.msg);
        });
    },
    deleteCategory(data) {
      this.$confirm(`确定删除分类「${data.name}」吗？`, '提示', { type: 'warning' })
        .then(() => {
          knowledgeCategoryDeleteApi(data.id).then((res) => {
            this.$message.success(res.msg);
            this.getCategory();
            if (this.where.cat_id === data.id) {
              this.where.cat_id = 0;
              this.getList();
            }
          });
        })
        .catch(() => {});
    },
    beforeUpload(file) {
      const maxSize = 50 * 1024 * 1024;
      if (file.size > maxSize) {
        this.$message.error('单个文件不能超过 50MB');
        return false;
      }
      return true;
    },
    async customUpload(options) {
      const formData = new FormData();
      formData.append('file', options.file);
      formData.append('cat_id', this.where.cat_id || 0);
      this.uploading = true;
      try {
        const res = await knowledgeFileUploadApi(formData);
        options.onSuccess(res);
        this.$message.success(`${options.file.name} 上传成功，正在解析`);
        try {
          await knowledgeFileParseApi(res.data.id);
          this.$message.success(`${options.file.name} 解析完成`);
        } catch (e) {
          this.$message.error(`${options.file.name} 解析失败：${e.msg || ''}`);
        }
        this.getList();
        this.getCategory();
      } catch (e) {
        options.onError(e);
        this.$message.error(e.msg || '上传失败');
      } finally {
        this.uploading = false;
      }
    },
    doParse(row) {
      this.parsingId = row.id;
      const loading = this.$loading({ lock: true, text: `正在解析「${row.name}」...`, background: 'rgba(0,0,0,0.2)' });
      knowledgeFileParseApi(row.id)
        .then((res) => {
          loading.close();
          this.parsingId = 0;
          this.$message.success(res.msg);
          this.getList();
        })
        .catch((res) => {
          loading.close();
          this.parsingId = 0;
          this.$message.error(res.msg || '解析失败');
          this.getList();
        });
    },
    showChunks(row) {
      this.chunkFile = row;
      this.chunkWhere.page = 1;
      this.chunkDialog = true;
      this.loadChunks(row.id);
    },
    loadChunks(id) {
      this.chunkLoading = true;
      getKnowledgeFileChunksApi(id, this.chunkWhere)
        .then((res) => {
          this.chunkList = res.data.list;
          this.chunkTotal = res.data.count;
          this.chunkLoading = false;
        })
        .catch((res) => {
          this.chunkLoading = false;
          this.$message.error(res.msg);
        });
    },
    chunkPageChange(index) {
      this.chunkWhere.page = index;
      this.loadChunks(this.chunkFile.id);
    },
    deleteFile(row) {
      this.$confirm(`确定删除文件「${row.name}」吗？分块数据会一并删除。`, '提示', { type: 'warning' })
        .then(() => {
          knowledgeFileDeleteApi(row.id).then((res) => {
            this.$message.success(res.msg);
            this.getList();
            this.getCategory();
          });
        })
        .catch(() => {});
    },
    doSearch() {
      if (!this.searchForm.question) {
        return this.$message.warning('请输入要检索的问题');
      }
      this.searchForm.cat_id = this.where.cat_id || 0;
      this.searchLoading = true;
      knowledgeSearchApi(this.searchForm)
        .then((res) => {
          this.searchResult = res.data;
          this.searchLoading = false;
        })
        .catch((res) => {
          this.searchLoading = false;
          this.$message.error(res.msg);
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.kb-page {
  display: flex;
  align-items: flex-start;
  .kb-side {
    width: 260px;
    flex: 0 0 260px;
    margin-right: 14px;
  }
  .kb-main {
    flex: 1;
    min-width: 0;
  }
}
.kb-side-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  .kb-side-title {
    font-weight: 600;
    color: #303133;
  }
}
.kb-tree-wrap {
  padding: 10px 8px 16px;
  max-height: 720px;
  overflow: auto;
}
.kb-node {
  display: flex;
  align-items: center;
  width: 100%;
  padding-right: 6px;
  font-size: 13px;
  overflow: hidden;
  .kb-node-label {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .kb-node-count {
    margin-left: 6px;
    color: #909399;
    font-size: 12px;
  }
  .kb-node-ops {
    display: none;
    margin-left: 8px;
    a {
      font-size: 12px;
      margin-left: 6px;
    }
  }
  &:hover .kb-node-ops {
    display: inline-block;
  }
}
.kb-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  .kb-toolbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .kb-name-input {
    width: 200px;
  }
  .kb-status-select {
    width: 130px;
  }
}
.kb-tip {
  margin-top: 4px;
  font-size: 12px;
  color: #909399;
}
.kb-chunk-list {
  max-height: 520px;
  overflow: auto;
}
.kb-chunk-item {
  border: 1px solid #ebeef5;
  border-radius: 4px;
  padding: 10px 12px;
  margin-bottom: 10px;
  .kb-chunk-head {
    display: flex;
    gap: 16px;
    color: #909399;
    font-size: 12px;
    margin-bottom: 6px;
  }
  .kb-chunk-content {
    font-size: 13px;
    color: #303133;
    line-height: 22px;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 180px;
    overflow: auto;
  }
}
.kb-empty {
  text-align: center;
  color: #909399;
  padding: 30px 0;
}
.kb-search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  .kb-limit {
    width: 110px;
  }
}
.kb-search-mode {
  font-size: 12px;
  color: #909399;
  margin-bottom: 10px;
}
.mb15 {
  margin-bottom: 15px;
}
</style>
