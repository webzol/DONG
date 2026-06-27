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

## v1.2.0(2026-06-26)· 融合借鉴 suxing.me

### 背景
- 用户要求参考 https://www.suxing.me/(暗黑 3 栏轻社区博客)开发。已拍板:**融合借鉴**(保留 Apple 质感 + 双模式 + 双栏,吸收其内容呈现),**不推翻** v1.1.0;配色**保留双模式**。用 ui-ux-pro-max skill 验证(Bento 卡片 / lazy+srcset / About-Author member-count)。
- 取舍:文章卡 = 横向 media-object(左封面图 + 右内容),吸收 suxing.me;质感/布局/双模式沿用 v1.1.0。新增**后台可配置**(Customizer 开关)。

### 改动
- **文章卡重构**(`template-parts/content.php` + `assets/css/layout.css`):`.post-card` 由纯文字卡改为 grid 两列(`clamp(7rem,22%,10rem) minmax(0,1fr)`),封面 `aspect-ratio:4/3` + `object-fit:cover` + `loading="lazy"`;无特色图回退 `.post-card--no-thumb`(单列纯文字卡,即 v1.1.0 样式)。新增 stats 区:浏览数 · 评论数 · 字数时长 + 标签药丸(限 3 个)。`≤640px` 转竖排 + 封面 16:9 banner。
- **stretched-link 层级**:封面 `<a class="post-card__thumb">` z-index:2(高于标题 `::after` 的 1)+ `tabindex="-1"`(整卡可点兜底,封面不重复进键盘流)。
- **图标规范**:新增 `onedong_get_icon/icon()` 内联 SVG(零依赖,calendar/eye/chat/clock/hash/user);**禁用 emoji 当 UI 图标**(skill 硬规则)。注:`header.php` 三态切换的 emoji(☀️🌙🖥️)是 v1.1.0 既有,改它要重写三态 CSS,本期不动(后续可选)。
- **浏览计数**(`functions.php`):WP 无原生浏览数,零依赖 post_meta `_onedong_views` + IP+UA 指纹 transient(6h)防刷新;挂 `template_redirect`(非 `wp_head`,后者会在 feed/REST 触发);`is_singular('post') && is_main_query()` + 排除管理员。`onedong_get_views()` 读取。
- **图片尺寸**:`add_image_size('onedong-card', 600, 450, true)`(4:3 裁剪)。
- **后台配置**(Customizer,`functions.php` `onedong_customize_register`):新增 `onedong_cards` + `onedong_sidebar` 两个 section。开关:封面图 / 浏览数(同时控计数)/ 评论数 / 字数时长 / 标签;范围:摘要行数 1-6(经 `wp_add_inline_style` 注入 `--excerpt-lines` 变量到 layout.css);侧栏:文章/评论总数开关 + 头像来源(logo/gravatar/none)。默认全开。sanitize:`onedong_sanitize_checkbox` / `onedong_sanitize_avatar_source`。
- **作者卡增强**(`sidebar.php`):profile 卡加头像(`custom_logo` 或 `admin_email` 的 gravatar)+ 文章总数(`wp_count_posts`)+ 评论总数(`wp_count_comments`)。
- **footer-widgets 渲染**(可选,补 v1.0 遗留缺口):`footer.php` 补 `is_active_sidebar('footer-widgets')` + `dynamic_sidebar`。
- 版本 1.1.0→1.2.0(`style.css` + `ONEDONG_VERSION`,刷资产 URL 缓存)。

### 坑 / 注意
- **`add_image_size` 老文章无 `onedong-card` 尺寸**:WP 自动 fallback 到 large/full(横向卡里偏大/失真)。上线后跑一次 **Regenerate Thumbnails** 回填。
- **line-clamp 与 `<p>`**:`.post-card__summary` 用 `-webkit-box` + `line-clamp:var(--excerpt-lines,2)`,内含 `the_excerpt()` 的 `<p>` 需 `display:inline` 才能正确按行截断。
- **计数并发写**:博客量级可接受轻微丢失(不走 `$wpdb` 原子 UPDATE,保留 WP 抽象)。
- **CDN**:改主题后外网要刷腾讯云 CDN(43.153.219.55);`ONEDONG_VERSION=1.2.0` 自动给 CSS URL 加 `?ver=1.2.0` 绕过浏览器缓存。
- 完整方案见 plan:`C:\Users\Administrator.DESKTOP-VIVLMOS\.claude\plans\drifting-sparking-taco.md`。

### 后续可选(scope 外)
无限下拉加载、comments.php 评论功能、3 栏布局、AOS 动画、转暗黑为主、toggle emoji→SVG、Article schema、浏览数排序小工具。

## v2.0.0(2026-06-26)· 高度还原 suxing.me(推翻 v1.2.0 方向)

### 背景
- 用户要求「跟 suxing.me 一样」,v1.2.0 的 Apple 双栏**融合**方向被否。改 **高度还原 suxing.me(Alright 主题)** + **保留双模式**。
- 抓 suxing 的 reset.css/style.css,获精确设计系统(gray 灰阶 8 级 + blue #3858F6 + 12px 圆角 + list-item 卡 + 浅/暗双模式变量)。

### 改动
- **tokens.css 全换 suxing 配色**:gray 灰阶 + blue primary **固定**(去掉 `--hue` 色相滑块)+ 浅白底/暗黑底双模式(暗色 `#0f0f11` 底 + `#202022` 卡,`box-shadow:none`)。
- **layout.css**:双栏 → **三栏**(`16rem 主 16rem`,≤1180 降双栏、≤992 单栏);文章卡改 suking **list-item**(上图下文,封面 16:9 padding-top 撑比例 + object-fit cover + hover scale 1.05,12px 圆角);h2 primary 色条、正文链接红色 inset 下划线、药丸标签、毛玻璃 navbar。
- **content.php**:上图下文,保留浏览数/评论数/标签/字数 + Customizer 开关。
- **sidebar-left.php(新建)**:左作者卡(头像 96px + 文章/评论总数)。sidebar.php 改纯右侧栏(分类+标签)。
- **header.php**:去色相滑块,留三态切换。theme-toggle.js 去 HUE_KEY/initHue。
- **functions.php**:v2.0.0,去 hue 注入 + Customizer onedong_hue;保留浏览计数/图标/卡片开关/Prism/footer-widgets。
- **home/archive/search.php**:three-col + `get_sidebar('left')`。

### 坑 / 注意
- 抓 suxing 两份完整 CSS 消耗大量 context,剩余重做**委派给 fresh 子 agent** 完成(避免主会话 context 中断留半成品)。
- dingxudong.com **现跑 Once-main 主题**(非 OneDong),需 WP 后台启用 OneDong + 刷腾讯云 CDN + Regenerate Thumbnails。
- **分支**:feat/onedong-v2.0(最终方案);feat/onedong-v1.2.0 废弃(融合方向,被 v2.0 取代)。
- **还差细节**:~~分类贴片位置、中栏宽度~~ → 已在 v2.1.0 补完。

## v2.1.0(2026-06-27)· v2.0 收尾 + toggle SVG

### 改动
- **中栏宽度**:`--site-width` 1200→1280,对齐 suxing ≈1280。
- **分类贴片**(`template-parts/content.php` + `assets/css/layout.css`):分类从 meta 行移到**封面图左上角**贴片(`.post-card__cat-badge`),取主分类 `$cats[0]`;贴片 `pointer-events:none` 纯展示,点击穿透到封面/整卡链接(规避 `<a>` 嵌套 `<a>` 非法)。**无特色图**时分类仍留 meta 行(带 hash 图标,可点)。
- **toggle emoji→SVG**(`header.php` + `functions.php` + `layout.css`):三态切换 ☀️🌙🖥️ 换成内联 SVG;`onedong_get_icon` 新增 `sun`/`moon`/`monitor` 三图标;`.theme-toggle__icon` 显示态 `display:inline`→`inline-flex`(图标居中)+ `.icon` 1.1rem。符合「禁用 emoji 当 UI」规则(v1.2 遗留的 header emoji 至此清零)。
- 版本 2.0.0→2.1.0(`style.css` + `ONEDONG_VERSION`,刷资产 URL 缓存)。

### 坑 / 注意
- 开发机无 php,`php -l` 未跑,待线上/本地 WP 启用时复验(建议 `Theme Check` 插件复查)。
- 分类贴片 `pointer-events:none` → 贴片本身不可点跳分类页(纯展示);若需可点,需把封面 `<a class="post-card__thumb">` 重构为非链接容器,但会牵动 stretched-link 整卡点击逻辑,本期不动。
