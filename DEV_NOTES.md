# DEV_NOTES — OneDong WordPress 主题

> 记录开发决策与坑,方便后续接手。

## 背景

- **源材料不是网站模板**,而是本仓库的 **Fuwari Typora 主题**(`fuwari-light.css` + `fuwari-assets/fuwari-base.css`,为 Typora 编辑器写的纯 CSS)。`onedong/` 是在同一套 Fuwari 视觉语言上构建的 **WordPress 经典博客主题**。
- 用户(TD)决策:**经典主题**(非 FSE)、**完整博客**(不含评论模板 / 侧栏)、**浅色 + 暗色**、命名 **OneDong**(slug / Text Domain `onedong`)。

## 关键决策

### 1. 样式映射:`#write` → `.entry-content`
源 Typora CSS 用 `#write` 作文章容器。OneDong 用 `.entry-content`(WP 经典主题惯例)承载正文排版;布局组件(`.post-card` / `.site-header` / `.pagination`)单独放 `layout.css`。
**移除**了所有 Typora 专属选择器:`.CodeMirror*`、`.cm-s-inner*`、`#typora-sidebar`、`.md-*`、`#top-titlebar`、源码模式等。同时补全了古腾堡块类(`.wp-block-quote` / `.wp-block-table` / `.wp-block-code` / `.alignleft` 等),让块编辑器输出也美观。

### 2. 暗色模式实现
- 方案:`<html data-theme="dark">` + CSS 变量,而**非** `prefers-color-scheme` 媒体查询或 `light-dark()`。
- 原因:需求是「系统跟随 + **手动切换 + 记忆**」,纯媒体查询做不到手动覆盖;`light-dark()` 只能跟随系统。`data-theme` 方案三者都满足。
- **防闪烁(FOUC)**:`header.php` 在 `<head>` 内联一小段同步 JS,首帧渲染前据 localStorage / 系统设置 `data-theme`。

### 3. 代码高亮 = Prism.js(CDN)
- 经典主题正文是静态 HTML 无着色,故引入 Prism(core + autoloader,jsDelivr CDN),按需加载语言组件,零本地依赖。
- 古腾堡 `core/code` 默认输出无 `language-*` 类 → Prism 不高亮 → 用 `render_block` 过滤补 `language-markup` 回退(`onedong_code_block_language`)。
- token 配色在 `assets/css/code.css`,走 CSS 变量 `--code-token-*`,浅暗色自动切换。

### 4. theme.json 在经典主题
经典主题放 `theme.json` 不改 PHP 渲染,但能让古腾堡颜色选择器 / 字体出现主题项,编辑器预览更贴合。`styles` 用 `var(--page-bg)` 等,与前端一致。暗色在编辑器内的实时预览暂未深入( WP 该能力仍在演进),前端暗色完全由 CSS 变量保证。

### 5. 主色可调
`--hue` 单变量驱动整套 OKLCH 调色板。Customizer 滑块经 `wp_add_inline_style` 注入 `:root{--hue:N}`;为 250(默认)时跳过注入省一次请求。

## 暗色令牌取值(按 hue 250 反演)
见 `assets/css/tokens.css`:page `oklch(0.18 .018)`、card `oklch(0.225 .022)`、text `oklch(0.93 .012)`、primary 提亮到 `0.72`(暗底保证对比度)、code-bg `oklch(0.20 .018 260)`。

## 已知限制 / 后续
- **无本地 PHP**:开发机无 php,无法 `php -l`;已在本地 WP 启用时再验。建议用 `Theme Check` 插件复查。
- **无评论模板**:按范围未含 `comments.php`;`single.php` 未调 `comments_template()`。若需评论,新建 `comments.php` 并在 `single.php` 末尾加调用。
- **侧栏 widgets**:`functions.php` 注册了 `footer-widgets` sidebar,但模板暂未渲染。如需,在 `footer.php` 顶部加 `is_active_sidebar('footer-widgets')` 输出。
- **截图**:`screenshot.png` 1200×900,由用户提供的封面图(原图 1448×1086)Pillow 居中裁剪 + resize 生成。

## Git
`onedong/` 纳入现有仓库 `webzol/dongS`(原 Typora 主题仓库)作为子目录。亦可单独打包 zip 上传 WordPress。

## v1.1.0(2026-06-26)· Apple 风双栏重构

### 背景
- 用户要求:① 1:1 对齐 `fuwari.vercel.app` 演示布局;② 配色/质感参考 Apple 官店(`www.apple.com.cn/store`)。
- 取舍:文章卡片 = 纯文字卡(去封面,对齐演示);默认色相 215(苹果蓝,原 250);布局 = Fuwari 双栏,质感 = Apple。

### 改动
- **tokens.css** 整盘换 Apple 配色:page `#fbfbfd`、card `#fff`、text `#1d1d1f`、muted `#6e6e73`、primary 苹果蓝(`oklch(.56 .19 var(--hue))`,215 时≈`#0071e3`)、shadow 极弱、圆角加大(large 1.25rem);暗色纯黑底 `#000` + `#1d1d1f` 卡。字体 SF Pro 优先。`--site-width` 1100→1200 适配双栏。
- **base.css** 全局链接去掉 Fuwari 虚线下划线,改 Apple 风(蓝、无默认下划线、hover 下划线)。
- **双栏**:`home/archive/search` 包 `site-content--two-col` grid(主内容 `minmax(0,1fr)` + 侧栏 `17rem`),≤1024px 降单栏。新建 `sidebar.php`(profile=站点名+副标题 / 分类带 count / 标签云)经 `get_sidebar()` 引入。
- **文章卡片重做**(`content.php`):纯文字卡,去特色图与「阅读全文」;整卡可点(stretched link:`.post-card__title a::after` 绝对铺满 z-index:1,卡内其余链接 z-index:2 浮于其上);新增 `.post-card__stats` 字数·时长。`post-list` 由网格改单列 flex。
- **顶栏**(`header.php` + `theme-toggle.js`):加色相滑块(`#hue-slider` 彩虹渐变轨道)+ 主题切换改三态(☀️亮 / 🌙暗 / 🖥️跟随系统),pref 三态循环,auto 时监听 `prefers-color-scheme` 实时跟随;滑块实时改 `--hue` 并 localStorage 记忆。anti-flash 兼容 auto。
- **字数/阅读时长**:`functions.php` 加 `onedong_reading_stats()`(去标签+去空白后 `mb_strlen`,/300 字每分)。
- 版本号 1.0.0→1.1.0(刷新资产 URL 缓存)。

### 坑
- **Windows `Compress-Archive` 打的 zip 用反斜杠路径**(`onedong\functions.php`),Linux `unzip` 虽能智能还原但告警且不可靠。**改用 Python `zipfile` 显式 `replace(os.sep,'/')` 打包**(`/tmp/zip.py`)才规范——后续打包一律用此法。
- 部署首次因 zip 反斜杠 + `set -e` 致脚本中途退出(属主停在 root、校验未跑完)。改用「不用 `set -e` + 每步显式检查」后稳定。
- 线上 `dingxudong.com` 走腾讯云 CDN(`43.153.219.55`,非源站 `150.158.16.80`),改主题后**外网要刷 CDN 才生效**;源站(本机 curl `127.0.0.1`)实时正确。**按用户分工,线上/CDN 由用户自管,开发机只负责开发 + scp 上传。**

### 服务器备忘
- 主题路径 `/www/wwwroot/dingxudong.com/wp-content/themes/onedong`,属主 `www:www`,目录 755 / 文件 644。
- wp-cli 在 `/usr/local/bin/wp`(ghfast.top 镜像下载),PHP `/www/server/php/83/bin/php`(8.3.27);MySQL 5.5.62。
