

# DeepSeek AI 自动摘要插件  

![插件版本](https://img.shields.io/badge/version-1.0.6-blue.svg)  
![WordPress兼容版本](https://img.shields.io/badge/WordPress-%3E=5.0-success.svg)  
![许可证](https://img.shields.io/badge/license-GPL--2.0+-brightgreen.svg)  


## 简介  
**DeepSeek AI 自动摘要** 是一款基于 [DeepSeek AI](https://www.deepseek.com/) 大语言模型开发的 WordPress 插件，用于自动为文章生成简洁明了的摘要。插件支持通过短代码灵活调用，适用于博客、资讯类网站等需要快速生成内容摘要的场景。  


## 核心功能  
1. **智能摘要生成**  
   - 基于 DeepSeek 大语言模型，自动提取文章核心内容，生成 50-100 字的精简摘要。  
   - 支持截断过长内容（默认最大处理 2000 字），避免 API 调用超限。  

2. **灵活的短代码调用**  
   - 基础用法：`[deepseek_summary]`（自动获取当前文章摘要）。  
   - 自定义参数：  
     ```plaintext  
     [deepseek_summary 
         post_id="文章ID"        // 自定义文章ID（默认当前文章）
         title="AI摘要"         // 自定义标题
         toggle="true"          // 是否开启折叠功能（true/false，默认true）
         auto_open="false"      // 是否自动展开摘要（true/false）
     ]  
     ```  

3. **优雅的前端展示**  
   - 内置响应式 CSS 样式，支持折叠/展开交互，适配移动端。  
   - 包含加载状态、错误提示等反馈机制，提升用户体验。  

4. **后台配置管理**  
   - 支持配置 DeepSeek API 密钥、模型名称、温度值等参数。  
   - 可设置内容最大处理长度、生成摘要的最大 Token 数。  


## 安装与配置  
### 步骤 1：安装插件  
1. 在 WordPress 后台进入 **插件 > 安装插件**。  
2. 搜索 “DeepSeek AI 自动摘要” 或上传插件压缩包。  
3. 激活插件。  

### 步骤 2：配置 DeepSeek API 密钥  
1. 进入 WordPress 后台 **设置 > DeepSeek AI 摘要**。  
2. 填写从 [DeepSeek 平台](https://www.deepseek.com/) 获取的 API 密钥。  
3. 可选配置：调整模型名称（默认 `deepseek-chat`）、温度值（控制输出随机性，0-2）等参数。  

### 步骤 3：创建数据表（首次使用需手动操作）  
执行以下 SQL 语句（需在数据库管理工具中操作）：  
```sql  
ALTER TABLE `wp_posts` ADD `summary` TEXT NULL DEFAULT NULL AFTER `post_content`;  
```  


## 使用示例  
### 示例 1：默认调用（当前文章摘要）  
```plaintext  
[deepseek_summary]  
```  
**效果**：在文章中插入可折叠的 AI 摘要，标题为 “AI 摘要”，默认折叠显示。  

### 示例 2：自定义标题并固定显示  
```plaintext  
[deepseek_summary title="文章要点" toggle="false"]  
```  
**效果**：标题改为 “文章要点”，摘要始终展开显示，不包含折叠按钮。  

### 示例 3：指定其他文章 ID  
```plaintext  
[deepseek_summary post_id="123"]  
```  
**效果**：生成 ID 为 123 的文章摘要（需确保该文章存在）。  


## 开发信息  
### 技术栈  
- 后端：WordPress PHP API + cURL 网络请求。  
- 前端：CSS3 动画 + JavaScript（原生 Fetch API）。  
- 依赖：DeepSeek Chat API（`deepseek-chat` 模型）。  

### 贡献与反馈  
欢迎通过 GitHub 提交 Issue 或 Pull Request 参与开发：  
[插件开源仓库地址](https://github.com/your-username/deepseek-ai-summary)（请替换为实际仓库链接）  

如需技术支持或反馈问题，请联系：  
邮箱：`your-email@example.com`（请替换为实际邮箱）  


## 许可证  
本插件基于 **GPL-2.0+ 许可证** 开源，允许自由修改和分发，但需保留原作者信息及版权声明。  


## 更新日志  
### v1.0.6（2025-05-23）  
- 优化短代码默认参数，支持纯标签调用 `[deepseek_summary]`。  
- 增强移动端适配，修复折叠按钮在小屏幕的显示问题。  
- 添加 URL 参数自动展开功能（`?deepseek_summary=1`）。  

### v1.0.5（2025-05-22）  
- 合并 CSS/JS 代码到单个文件，简化插件结构。  
- 修复自动添加摘要功能残留问题，仅保留短代码调用方式。  

### v1.0.1（初始版本）  
- 实现基础摘要生成功能，支持短代码和后台配置。  


## 鸣谢  
- 感谢 [DeepSeek 团队](https://www.deepseek.com/) 提供的大语言模型服务。  
- 参考 WordPress 官方插件开发文档及社区最佳实践。  
