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

## v2.2.0(2026-06-27)· 评论 + Article schema + 滚动入场动画

### 改动
- **评论功能**(`comments.php` 新建 + `single.php` + `functions.php` + `assets/css/layout.css`):补齐 v1.0 遗留缺口。
  - 新建 `comments.php`:评论列表(自定义回调 `onedong_comment_callback`:头像 + 作者 + 日期 + 内容 + 回复/编辑)+ pingback/trackback 简化行 + 分页 + `comment_form()` 表单。
  - `single.php` 末尾挂 `comments_template()`(`comments_open() || get_comments_number()` 才显示)。
  - 回调设 `$GLOBALS['comment']`(评论惯例,`comment_text`/`comment_author_link` 等依赖全局);`comment_reply_link` 用 `div-comment` 锚点(嵌套回复 add_below)。
  - layout.css 补 `.comments-area` 全套(列表 / 嵌套缩进 / 头像 / 待审药丸 / 表单 / 取消回复 / 移动端)。
- **Article schema**(`functions.php`):`onedong_article_schema()` 挂 `wp_head`,仅单篇 post 输出 `BlogPosting` JSON-LD(headline / datePublished / dateModified / author / publisher+logo / image / description / mainEntityOfPage)。无特色图时 image 回退站点图标。
- **滚动入场动画**(零依赖自写 IntersectionObserver,**非** AOS 库,符合主题零依赖调性):`assets/js/reveal.js` 给 `[data-reveal]` 入视口加 `.is-revealed`;`template-parts/content.php` 文章卡加 `data-reveal`。反 FOUC:`<html class="no-js">` + header anti-flash 脚本首帧前同步替换 no-js→js,CSS 用 `html.js [data-reveal]{opacity:0}` 隐藏;**无 JS / 无 IO 兜底可见**;`prefers-reduced-motion` 禁用。侧栏首屏可见未加(避免初始隐藏影响布局感知)。
- 版本 2.1.0→2.2.0(`style.css` + `ONEDONG_VERSION`,刷资产 URL 缓存)。

### 坑 / 注意
- 开发机无 php,`php -l` 未跑,待线上/本地 WP 启用时复验(建议 `Theme Check` 插件 + Google 富结果测试验 schema + 后台开启评论实测)。
- `get_the_excerpt( $post_id )` 在 `wp_head` 阶段调用:WP 主查询已 setup 单篇,可用;已 `wp_strip_all_tags` 防 HTML 进 description。
- 评论回调 `$GLOBALS['comment']`:phpcs 报 `WordPress.WP.GlobalVariablesOverride`,加 `ignore` 注释(标准主题惯例)。
- **评论默认需后台开启**:「设置 → 讨论」勾「允许他人提交评论」;主题只出模板,开关由站点控制。
- 入场动画:`[data-reveal]` 初始 `opacity:0`,依赖 JS 把 no-js→js 才隐藏;若 JS 禁用则 `<html>` 保持 no-js、元素可见,**无内容丢失风险**(对 SEO/可访问性友好)。

## v2.3.0(2026-06-27)· 后台模块化侧栏 + 宽屏可调

### 背景
- 用户(TD)要:① 左右侧栏后台自定义显示哪些模块;② 可新增文章列表/标签/归档等模块;③ 站点宽度(宽屏)可调。用 ui-ux-pro-max skill 验证 UX(field-grouping / progressive-disclosure / input-helper-text)。
- 取舍:**固定顺序 + 开关**(模块顺序在模板硬编码,后台只勾选显示哪些),**不做**拖拽排序(避免自定义 sortable 控件的复杂度与测试成本);宽屏用 **range 滑块**(1100–1600,默认 1280),与「摘要行数」滑块体验一致。本机无 Python → skill 的 search.py 跑不了,直接用 skill 文档内 §5 §8 规则指导。

### 改动
- **Customizer 扩展**(`functions.php` `onedong_customize_register`):
  - 新 section `onedong_layout`(priority 30):`onedong_site_width` range 1100–1600 默认 1280(step 20);`onedong_widget_count` range 3–10 默认 5(最新/热门文章条数,左右栏共用,避免控件爆炸)。
  - `onedong_sidebar` 重命名「左侧栏模块」+ description;加开关 `onedong_left_author/text/recent/popular`(仅 author 默认开)+ textarea `onedong_left_textarea`(`wp_kses_post`)。
  - 新 section `onedong_sidebar_right`(priority 33):开关 `onedong_right_cats/tags/recent/popular/archive/text`(cats/tags 默认开)+ textarea `onedong_right_textarea`。
- **模块渲染函数**(`functions.php`):`onedong_widget_recent_posts()` / `onedong_widget_popular_posts()`(按 `_onedong_views` meta 的 `meta_value_num` DESC,复用浏览计数)/ `onedong_widget_archive()`(`wp_get_archives` monthly limit12 show_post_count)/ `onedong_widget_text($side)`(读 textarea,`wp_kses_post` 输出)。各包 `.widget` 容器,复用现有卡片样式。
- **宽屏注入**(`onedong_scripts`):`onedong_site_width` 非 1280 时 `wp_add_inline_style('onedong-layout', ':root{--site-width:Npx}')`,clamp 1100–1600(仿 `onedong_excerpt_lines` 模式)。
- **模板改造**:`sidebar-left.php` / `sidebar.php` 按固定顺序 + `get_theme_mod` 开关依次渲染模块;全关时 `return`(不输出空 aside)。
- **样式**(`layout.css`):`.widget-posts`(缩略图 3rem + 两行截断标题 + 日期/浏览数)、`.widget-archive`、`.widget-text`;复用 `.widget` / `.widget-title`。
- 版本 2.2.0→2.3.0(`style.css` + `ONEDONG_VERSION`,刷资产 URL 缓存)。

### 坑 / 注意
- **热门文章依赖 `_onedong_views` meta**:无浏览记录的老文章不上榜(产生浏览后才出现);若要兜底可加 `meta_query` EXISTS 或 date fallback,本期不做。
- **全关侧栏不输出 aside**:三栏 grid 仍保留列宽(留白);默认 author/cats/tags 开,不会出现空栏。
- 开发机无 php/WP,`php -l` 与后台实测未跑,待上线「外观 → 自定义」逐开关复验 + 前端实测。
- **theme_mod key 一致性**:每个开关 key 在「注册(default+sanitize)→ 模板 get_theme_mod 读取」两处一致(grep 自检过)。

## v2.3.1(2026-06-27)· 换主题封面 + 内置默认缩略图

### 改动
- **主题封面** `screenshot.png`:换用用户提供的新封面(原图 1448×1086 jpg),PowerShell + System.Drawing 等比缩放到 1200×900 png(本机无 Pillow,走 .NET)。原图与目标同为 4:3,纯缩放无裁剪。
- **内置默认缩略图** `assets/img/default-thumb.png`(600×450 png):`template-parts/content.php` 无特色图时**不再降级为纯文字卡**,改用默认缩略图当封面(`get_theme_file_uri('assets/img/default-thumb.png')`,`object-fit:cover` 适配 16:9 封面区),分类贴片照常贴封面。仅当 Customizer「显示封面图」关闭(`onedong_show_thumbnail=0`)时才回退纯文字卡 + meta 行分类。
- 变量重构:`$has_thumb`(原含开关)拆为 `$show_thumb`(开关)+ `$has_thumb`(纯 `has_post_thumbnail()`);meta 行分类回退条件 `! $has_thumb` → `! $show_thumb`。
- 版本 2.3.0→2.3.1。

### 坑 / 注意
- 默认缩略图当前**仅列表卡**(content.php);`single.php` 页头、侧栏最新/热门文章缩略图无图时仍各自回退(不显示 / 占位图标),未统一铺开,如需一致可后续扩展。
- `screenshot.png` / `default-thumb.png` 为二进制,git 正常追踪;微信临时源图(`xwechat_files\...\temp`)可能被占用,已先 PowerShell 读尺寸再 FromFile 处理。
- `onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.2(2026-06-27)· 作者卡统计分离 + 黄V认证

### 改动
- **统计独立成卡**(`sidebar-left.php` + `layout.css`):作者卡的「文章数 / 评论数」从作者信息卡(头像+名字+描述)里**分离**,成为下方独立的 `.widget-profile-stats` 卡片(仍由 `onedong_left_author` + `onedong_show_author_stats` 开关控制),不再与头像信息挤在一起。原 `.widget-profile__stats` 的 `border-top` / `margin-top` 移除(独立卡自带 padding/边框)。
- **黄V认证图标**:头像右下角加 `.widget-profile__verified`(内联 SVG:黄圆 `#FFB300` + 白勾),卡色描边(`card-bg` 垫底 + `line-strong` ring)与头像描边一致,暗色模式自动适配。头像包 `.widget-profile__avatar-wrap`(relative)承载徽章。
- 版本 2.3.1→2.3.2。

### 坑 / 注意
- 黄V 固定黄色(认证色),不随暗色模式变。
- 统计卡与作者卡现为两个独立 `.widget`,中间有间距,视觉上"分开"。
- `onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.3(2026-06-27)· 页脚备案号 + 自定义版权

### 改动
- **页脚设置**(`functions.php` 新 section `onedong_footer` + `footer.php` + `layout.css`):
  - `onedong_footer_copyright`(textarea,`wp_kses_post`):自定义版权信息,留空显示默认「© 年份 站点 · OneDong 主题」。
  - `onedong_footer_icp`(text,`sanitize_text_field`):ICP 备案号,留空不显示。
  - `onedong_footer_icp_url`(url,`esc_url_raw`,默认 `https://beian.miit.gov.cn`):备案号点击跳转网址。
- `footer.php` `.site-info` 改 flex column:版权(自定义 / 默认)+ 备案号(可点击,`target=_blank rel=noopener nofollow`)。
- 版本 2.3.2→2.3.3。

### 坑 / 注意
- 备案号链接默认指向工信部 `beian.miit.gov.cn`,可后台改(如公安备案 `beian.mps.gov.cn` 可另填)。
- **无限下拉加载曾实现后按用户要求移除**(`infinite-scroll.js` 删除、enqueue / 样式撤销),保留传统 `the_posts_pagination` 分页。
- `onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.4(2026-06-27)· 作者卡昵称取后台 + 在线呼吸圆点

### 改动
- **作者名取后台昵称**(`sidebar-left.php`):作者卡名字由站点名(`get_bloginfo('name')`)改为**作者后台公开名称**——`get_user_by('email', admin_email)->display_name`,找不到用户时回退站点名。与头像(gravatar 也用 admin_email)同源。
- **在线状态呼吸圆点**:昵称右侧加 `.online-dot`(绿色 `#22c55e` 圆 + `box-shadow` 扩散呼吸动画 `onedong-pulse` 1.8s),`prefers-reduced-motion` 禁用。
- 版本 2.3.3→2.3.4。

### 坑 / 注意
- 取 `display_name`(后台「公开显示为」);若要 `nickname` 字段改 `->nickname`。
- admin_email 对应用户须存在且 display_name 已设,否则回退站点名。
- 绿色固定(在线色),不随暗色变;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.5(2026-06-27)· 文章卡作者 byline + 文章数图标

### 改动
- **文章卡作者 byline**(`template-parts/content.php` + `assets/css/layout.css`):文章卡 meta 行新增作者信息——头像(`get_avatar( get_the_author_meta('ID') )`,1.5rem 圆,带黄V `.post-card__verified`)+ 昵称(`the_author()`)+ 在线绿点(复用 `.online-dot`)。位置:标题下 meta 行,与日期同行(紧凑协调,不额外占行)。
- **文章数图标**(`sidebar-left.php` + `functions.php`):左栏作者卡「文章」数旁的 `#`(hash)换成 **`document`**(文档)图标,更贴切;评论数仍用 `chat`。
- 版本 2.3.4→2.3.5。

### 坑 / 注意
- 文章卡作者取**单篇文章作者**(`get_the_author_meta`),与左栏作者卡(取 `admin_email` 用户)在多作者博客可能不同;单作者博客一致。
- ⚠️ 线上 `dingxudong.com` 仍跑 **Once-main** 主题(非 OneDong),所有改动需部署 + 启用 OneDong + 刷腾讯云 CDN 才生效。
- `onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.6(2026-06-27)· 文章封面懒加载占位图

### 改动
- **懒加载占位图**(`assets/img/lazy-placeholder.png` + `assets/css/layout.css`):用户提供的图(`UI.png`)经 PowerShell `System.Drawing` resize 1448×1086 → 600×450(745KB→150KB)入主题。文章卡封面容器 `.post-card__thumb` 背景改用该占位图(`url('../img/lazy-placeholder.png') center/cover no-repeat`),特色图 / 默认缩略图 `<img>` 加载前由容器背景显示占位,加载完成后 `<img>`(`object-fit:cover`)覆盖。
- 版本 2.3.5→2.3.6。

### 坑 / 注意
- 占位走 CSS 容器背景(非 img `src`),`<img>` 仍在 DOM(SEO/可访问性不受影响);仅视觉加载态。
- 150KB PNG 占位仍偏大,如需更轻可转 jpg 或进一步缩小尺寸。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.7(2026-06-27)· 右栏模块顺序可调 + 左栏图片模块

### 改动
- **右栏模块顺序可调**(`functions.php` + `sidebar.php`):新增 `onedong_right_order`(text,逗号分隔 key,默认 `cats,tags,recent,popular,archive,text`)+ `onedong_sanitize_order`。抽出 `onedong_widget_cats()` / `onedong_widget_tags()`(原 sidebar.php 内联)+ `onedong_render_right_module($k)` 分发。`sidebar.php` 改为解析 order 顺序渲染(用户 order 优先,未列出的按默认顺序兜底),各模块仍受各自开关控制。
- **左栏图片模块**(`functions.php` + `sidebar-left.php` + `layout.css`):新增模块。Customizer 提供:开关 `onedong_left_image`、`WP_Customize_Image_Control`(`onedong_left_image_url`,支持上传 / 媒体库 / 粘贴地址)、标题 `onedong_left_image_title`、描述 `onedong_left_image_desc`(`wp_kses_post`)。`onedong_widget_image()` 渲染(图 + 标题 + 描述)。sidebar-left 顺序:作者卡 → **图片** → 文本 → 最新 → 热门。
- 版本 2.3.6→2.3.7。

### 坑 / 注意
- 顺序用 text 框填 key(**非拖拽**,WP Customizer 原生无拖拽排序控件,要做得写自定义 JS);description 已列 key 含义。
- `WP_Customize_Image_Control` 存的是图片 **URL**(非 attachment ID);前端 `<img src=url>`,外链图直接可用,无需媒体库。
- 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.8(2026-06-28)· 顶部导航居中对齐中间栏 + 封面图加载优化

### 改动
- **顶部导航对齐中间栏**(`assets/css/layout.css`):`.site-header__inner` 由 flex 改 **grid 三栏列对齐** `grid-template-columns: 16rem minmax(0,1fr) 16rem` + `column-gap:1.5rem`(与 `.site-content--three-col` 同列宽同间距)→ brand 落左栏列、nav 落中间栏列、控件落右栏列。nav `justify-self:start` 使其**左缘 = 中间栏左缘 = 下方文章流左缘**,顶栏与正文严格列对齐即「协调」。(初版用 `1fr auto 1fr` 让 nav 绝对居中,用户反馈偏右、要再往左对齐中间栏,遂改为列对齐 + 左对齐。)`.site-brand` `justify-self:start`、`.header-controls` `justify-self:end`。≤1180px(左栏隐藏)降 `display:flex`(brand+nav 靠左成组、控件 `margin-left:auto` 推右);≤768px flex-wrap + nav 占整行(order:3 width:100%)。
- **封面图加载优化**(`template-parts/content.php` + `single.php`):
  - **首屏 LCP 优先**:主查询第一篇(`$wp_query->current_post === 0`)封面改 `loading=eager` + `fetchpriority=high` + `decoding=async`(原全 `lazy` 导致首屏大图被延迟,拖慢 LCP);其余篇仍 `loading=lazy`。内页 `single.php` 页头特色图同理(LCP)eager+high。
  - **自适应 srcset/sizes**:封面图改用 `wp_get_attachment_image()` + 传 `sizes="(max-width:768px) 92vw,(max-width:1180px) 62vw,720px"`,WP 据注册尺寸(thumbnail/medium/onedong-card/medium_large/large)自动生成 srcset,浏览器按视口/DPR 选源——高 DPR 不糊、低带宽不浪费(原固定 `onedong-card` 单尺寸不自适应)。默认缩略图(无特色图)同步按 `is_lcp` 切 eager/lazy。
  - CSS 容器自适应基础已具备(`.post-card__img` width/height:100% + object-fit:cover,16:9 padding-top 容器 + 懒加载占位背景),本期补 HTML 层 srcset/sizes + LCP。
- 版本 2.3.7→2.3.8(`style.css` + `ONEDONG_VERSION`,刷资产 URL 缓存)。

### 坑 / 注意
- **fetchpriority 需 WP 6.3+**:`wp_get_attachment_image`/`the_post_thumbnail` 才认 `$attr['fetchpriority']` 原样输出;6.3+ 另有自动 LCP 增强(会跳过已手动设的,不重复)。低版本 fetchpriority 被忽略(无害降级),loading/decoding/sizes 全版本有效。
- **srcset 依赖原图够大 + 多尺寸已生成**:老文章若只生成了 `onedong-card`、原图 <768px,srcset 候选少 → 近似单尺寸;上线跑一次 Regenerate Thumbnails 补全 medium_large/large 后自适应才完整。
- **`sizes` 是渲染宽度 hint**:三栏中间栏约 720px、移动端近全宽;值是估值,浏览器据此选 srcset。
- **grid 列与 site-content 同宽对齐**:第 1/3 列固定 `16rem`(= 左右栏),第 2 列 `minmax(0,1fr)`(= 中间栏);brand/logo 若超 16rem 会溢入中列(边缘情况,常规 logo 不超)。
- 开发机无 php/WP,待上线复验(导航居中视觉 + Lighthouse LCP/srcset)。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.3.9(2026-06-28)· 右侧栏图片模块 + 图片模块左右栏共用

### 背景
- 用户反馈:① 左侧栏图片模块「新增了不显示」;② 右侧栏也要一个跟左侧栏一样的图片模块。
- 排查:左侧栏代码逻辑本身无误(开关 `onedong_left_image` 默认关 + URL 空 → 不显示),「不显示」多为开关未开 / Customizer 未发布;借此把图片模块**参数化**并补齐右侧栏。

### 改动
- **图片模块参数化**(`functions.php` `onedong_widget_image( $side = 'left' )`):原仅左侧栏的 `onedong_widget_image()` 改接收 `$side`,读 `onedong_{$side}_image_url/_title/_desc`;`sidebar-left.php` 调用改传 `'left'`。左/右栏渲染同源,样式复用 `.widget-image`;`<img>` 补 `decoding="async"`。
- **右侧栏图片模块**(与左侧栏一致,`functions.php` `onedong_sidebar_right` section):新增 `onedong_right_image`(开关,默认关)+ `onedong_right_image_url`(`WP_Customize_Image_Control`)+ `_title` + `_desc`。`onedong_render_right_module` 加 `'image'` case 调 `onedong_widget_image('right')`;`onedong_sanitize_order` valid + 默认 order 末尾 + fallback + description 均加 `image`;`sidebar.php` `$valid`/`$defs` 加 `image`(默认关)→ `$any_right` 循环与 `$sequence` 兜底自动覆盖。layout.css `.widget-image` 注释改「左/右共用」。
- 版本 2.3.8→2.3.9。

### 坑 / 注意
- **图片显示三条件(左/右栏同)**:① 勾「显示图片模块」开关 ② 填图片 URL ③ Customizer 点「发布」。左侧栏之前「不显示」多为开关默认关 / 未点发布,代码逻辑无误(已 grep 自检 key 注册↔读取一致)。
- 右侧栏图片默认关,顺序默认 `…,text,image`(末尾);要提前在「模块显示顺序」填 `image`(如 `cats,image,tags`)。
- `WP_Customize_Image_Control` 存 URL(非 attachment ID),外链图直接可用,无需媒体库。
- 开发机无 php/WP,待上线「外观 → 自定义 → 右侧栏模块」逐项实测。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.4.0(2026-06-28)· 文章详情页增强 + 文章卡作者行上移/stats 微博风

### A. 文章详情页增强(single.php)

- **TOC 文章目录**(`functions.php` `onedong_inject_heading_ids` 挂 `the_content` 优先级 20 + `onedong_toc()`):正则给 h2/h3 注入锚点 id(`sanitize_title` 生成 slug + 去重,已有 id 沿用),收集到全局 `$onedong_toc`;single.php 正文前渲染目录(**少于 2 个标题不显示**)。Customizer「文章详情页 → 显示文章目录(TOC)」开关。
- **顶部阅读进度条**(`assets/js/single.js` + `.reading-progress`):fixed 顶部 3px 进度条,按 `<article>` 可滚动进度填充,`requestAnimationFrame` 节流。z-index 60(高于 header 50)。仅 single 显示。
- **代码块复制按钮**(`single.js` + `.code-copy-btn`):遍历 `.entry-content / .comment-content pre` 注入复制按钮,`navigator.clipboard` 优先 + `execCommand` 兜底(非 HTTPS);hover 显示 / 触屏(`@media hover:none`)常显,适配 macOS 三圆点顶栏与双模式。
- **相关文章推荐**(`functions.php` `onedong_related_posts()`):按分类(不足按标签)`WP_Query` 取 N 篇,`post__not_in` 排除自身;single.php `post_nav` 后渲染 2 列网格卡(≤640 单列)。Customizer「显示相关文章」+ 条数(2–8)。
- `single.js` 仅 `is_singular('post')` enqueue;另含 TOC scrollspy(`IntersectionObserver` 高亮当前段)。
- Customizer 新 section `onedong_single`(priority 35):`onedong_show_toc` / `onedong_show_related` / `onedong_related_count`。

### B. 文章卡(列表 `template-parts/content.php`)

- **作者行移到标题上方**:原 `title → meta(作者)` 改为 `meta(头像+黄V+昵称+在线点+日期) → title`(作者在标题上方、图片下方正文区顶部)。布局仍 **上图下文**(封面 16:9 在顶)——用户初要求横向(media-object)后澄清「图片还是在上面」改回上图下文。
- **底部 stats 微博风**(`.post-card__stats`):项间距加宽(0.9→1.3rem)、图标 1rem、hover 变 primary,贴近微博「转发/评论/点赞」水平排列风格(数据项 = 浏览 / 评论 / 阅读时长,WP 无原生点赞)。
- 版本 2.3.9→2.4.0。

### 坑 / 注意
- **TOC id 依赖 the_content 过滤**:`onedong_inject_heading_ids` 挂 `the_content`(优先级 20,WP 自带过滤后);古腾堡 heading 块默认无 id,本过滤补上。`is_singular('post') && in_the_loop() && is_main_query()` 防误伤 feed/REST/副查询。
- **Clipboard API 需 HTTPS**:`navigator.clipboard` 仅 secure context(HTTPS/localhost)可用;非 HTTPS 走 `execCommand` 兜底(已实现)。
- **相关文章依赖分类/标签**:无分类无标签的独立文章不出相关;老文章需有分类。
- **阅读进度条 z-index 60** 贴屏幕顶,高于 header(50)。
- ⚠️ content.php 曾误写 `endthrough` + 重复 foreach 块(已修复,控制结构配对 grep 自检通过);开发机无 php,待上线 `php -l` + 逐功能实测(TOC/复制/进度条/相关文章)。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.4.1(2026-06-28)· 文章卡重新设计 + 点赞功能 + 默认缩略图设置

### A. 文章卡重新设计(`template-parts/content.php` + `assets/css/layout.css`)
- **新布局(自上而下)**:作者行(头像+黄V+昵称+在线点 + 发布时间)→ 标题 → 摘要 → 封面图(16:9 全宽)→ 底部三项(左阅读 / 中字数 / 右点赞)。
  - 作者行 + 发布时间移到图片**上方**(原 v2.4.0 在图片下方正文区);标题/摘要在作者行下、图片上。
  - `.post-card__body` 仅包图片上方内容(作者+标题+摘要,底部 padding 0.85rem);`.post-card__thumb` 全宽居中;`.post-card__stats` 独立成图片下方区(左右 + 底部 padding)。
- **底部三项均分**(`.post-card__stats` `justify-content:space-between`):阅读(eye)/ 字数(type)/ 点赞(heart),左中右。废弃原 views/comments/reading/tags 开关查询(固定三项)。

### B. 点赞功能(WP 无原生,自实现)
- **REST 路由**(`functions.php`):`POST /wp-json/onedong/v1/like { post_id }` → `_onedong_likes` post_meta +1;`permission_callback` 开放(匿名可赞),`post_id` 校验为 post。
- **`onedong_get_likes($post_id)`** 读取;前端 `assets/js/likes.js` 点 `.post-card__like` → fetch REST → +1,`localStorage`(key `onedong-liked-{id}`)防同一浏览器重复赞;已赞置 `is-liked`(心形 `#ff3b5c` 实心)。
- `wp_localize_script('onedong-likes','onedongLike',{url,nonce})` 注入 REST url + `wp_rest` nonce;likes.js 全局 enqueue(列表页有点赞按钮)。
- 点赞按钮浮于 stretched-link `::after`(z-index 1)之上(`.post-card__stats` z-index 2);`e.stopPropagation()` 阻止整卡跳转。

### C. 字数 + 图标
- **`onedong_word_count()`**:去标签去空白后 `mb_strlen`,供卡片「字数」项(区别于 `onedong_reading_stats` 的「字数·分钟」)。
- 新增图标 `heart`(点赞)、`type`(字数)。

### D. 默认缩略图设置
- Customizer「文章卡」section 加 `onedong_default_thumb`(`WP_Customize_Image_Control`):无特色图时用此图;留空回退主题内置 `assets/img/default-thumb.png`。`content.php` 无特色图分支读 `get_theme_mod('onedong_default_thumb')`,空则回退。

- 版本 2.4.0→2.4.1。

### 坑 / 注意
- **点赞匿名开放 + 前端防刷**:服务端不强制认证(允许匿名赞),防刷靠前端 localStorage(同一浏览器一次);清缓存/换浏览器可重复赞。严格防刷需服务端 IP/账号限流(本期不做)。
- **点赞数显示**:PHP 端 `number_format_i18n`(千分位),JS 端 +1 后用原始数字(无千分位);轻微不一致,可接受。
- **REST 需 pretty permalink**:`/wp-json/...` 需固定链接非默认;默认链接(`?rest_route=`)也兼容。
- **点赞按钮 vs stretched-link**:按钮在 `.post-card__stats`(z-index 2)内,高于标题 `::after`(1),可点;`stopPropagation` 防误跳整卡。
- **废弃开关**:`onedong_show_views/comments/reading/tags` 卡片不再查询(Customizer 控件保留,不影响);`onedong_reading_stats` 卡片不再用(函数保留)。
- 开发机无 php/WP,待上线 `php -l` + 点赞 REST 实测(curl POST)+ 卡片布局/默认缩略图实测。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.4.2(2026-06-28)· 文章卡封面图缩进对齐

### 改动
- **封面图缩进对齐**(`assets/css/layout.css` `.post-card__thumb`):封面图加 `margin: 0 1.1rem` 左右缩进(与 `.post-card__body` / `.post-card__stats` 的 `1.1rem` padding 对齐),去 `width:100%`(靠 flex stretch + margin),加 `border-radius: var(--radius-medium)` 自成圆角。作者 / 标题 / 摘要 / 封面图 / 底部数据**左边全部对齐成一条线**(v2.4.1 图全宽贴卡片边与正文错位 1.1rem;用户选「图与内容统一缩进对齐」方案)。
- 版本 2.4.1→2.4.2。

### 坑 / 注意
- 图缩进后左右露出 `card-bg`(图片内嵌、不再贴卡片边);16:9 `padding-top` 基于缩进后宽度(图高度略减)。
- 图角圆角 `--radius-medium`,不再依赖卡片 `overflow:hidden` 裁剪。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.4.3(2026-06-28)· 文章卡上下内边距收窄

### 改动
- `.post-card__body` padding-top `1rem → 0.6rem`(头像上方间距收窄)。
- `.post-card__stats` padding-bottom `1.05rem → 0.65rem`(底部阅读/点赞到卡片底的间距收窄)。
- 版本 2.4.2→2.4.3。

## v2.4.4(2026-06-28)· 文章卡上下内边距近无

### 改动
- `.post-card__body` padding-top `0.6rem → 0.2rem`(头像几乎贴卡片顶)。
- `.post-card__stats` padding-bottom `0.65rem → 0.25rem`(底部数据几乎贴卡片底)。
- 版本 2.4.3→2.4.4。

## v2.4.5(2026-06-28)· 文章卡上下内边距对齐侧栏作者卡

### 改动
- 文章卡上下内边距参照侧栏 `.widget`(padding `1.25rem 1.4rem`):
  - `.post-card__body` padding-top `0.2rem → 1.25rem`(头像到卡片顶,对齐作者卡头像上方间距)。
  - `.post-card__stats` padding-bottom `0.25rem → 1.25rem`(点赞行到卡片底,对齐作者卡底部间距)。
  - 左右 padding 仍 `1.1rem`(保持 v2.4.2 封面图缩进对齐不变)。
- 版本 2.4.4→2.4.5。

## v2.4.6(2026-06-28)· 文章卡作者头像放大(参考截图)

### 背景
- 用户给出参考截图(微博/suxing 风),要求中间栏文章卡「作者头像区域」参考该图调整。
- 对比截图 vs 现状,核心差异:**头像偏小**(现状 1.5rem/24px,截图 ~40px 圆形)。其余(黄V 右下角、横向排列)现状已具备。

### 改动(`assets/css/layout.css` + `template-parts/content.php`)
- **头像放大**:`.post-card__avatar` width/height `1.5rem → 2.5rem`(24px→40px),border `1.5px → 2px`(描边随尺寸等比),保留 card-bg 描边 + line-strong box-shadow 双线(与黄V 徽章垫底配套)。
- **黄V 徽章等比放大**:`.post-card__verified` `0.95rem → 1.3rem`,right/bottom `-0.2rem → -0.28rem`(大头像外缘位置微调)。
- **昵称加大配大头像**:`.post-card__author-name` `0.8rem → 0.875rem`(14px)。
- **头像-昵称间距**:`.post-card__author` gap `0.4rem → 0.5rem`。
- **头像源图分辨率**(`content.php`):`get_avatar` size `48 → 96`(适配 2x DPR,放大后不糊)。
- 版本 2.4.5→2.4.6(`style.css` + `ONEDONG_VERSION`,刷资产 URL 缓存)。

### 坑 / 注意
- **在线绿点保留**:参考截图里昵称旁无在线状态点,但 OneDong 的 `.online-dot`(v2.3.4 主动加的 OneDong 特色)本期**保留未删**;如需完全贴合参考图去掉,移除 `content.php` 里 `.online-dot` span 即可。
- **保留双线描边**:参考图头像「无明显描边」,但现状 card-bg 描边与黄V 徽章的 card-bg 垫底是配套的,去掉会让黄V 衔接突兀,故保留(仅等比加粗到 2px)。如想要参考图那种无描边内阴影风,可去 border/box-shadow 改 `box-shadow: inset 0 0 2px rgba(0,0,0,.1)`。
- 头像放大后 `.post-card__meta` 行变高(~40px),标题相应下移;顶部 padding 沿用 v2.4.5 的 1.25rem,视觉协调。
- ⚠️ 线上仍跑 Once-main;`onedong.zip` 仍为外部不明改动,未纳入本次提交。

## v2.4.7(2026-06-29)· 文章卡间距收紧 + 默认图自适应 + 图片模块可点击

### 背景
- TD 反馈三处:① 中间栏头像上方 / 点赞栏下方间距偏大;② 无特色图默认缩略图(品牌图)被 16:9 cover 裁切,上下文字与猫主体丢失;③ 左右栏图片模块希望可点击跳转、链接后台可设。

### 改动(`layout.css` + `content.php` + `functions.php`)
- **文章卡间距收紧**(撤销 v2.4.5「对齐侧栏 widget」取向,TD 嫌大):
  - `.post-card__body` padding-top `1.25rem → 0.75rem`(头像上方)。
  - `.post-card__stats` padding-bottom `1.25rem → 0.75rem`(点赞栏下方)。
- **默认缩略图自适应**(品牌图完整展示):
  - 根因:`default-thumb.png` 为 4:3(600×450,黑底 + 上 OneDong 标识/标语 + 下黑猫),16:9 容器 `cover` 裁掉上下。
  - `content.php` 默认图 `<img>` 加 `post-card__img--default`;CSS `object-fit:contain` + `background:#000`(匹配黑底消留白)。特色图仍 `cover`。
- **图片模块可点击跳转**:
  - 新增 `onedong_{left,right}_image_link` 设置(URL);后台「外观→自定义→左/右侧栏模块」填。
  - `onedong_widget_image()`:link 非空时图片+标题包 `<a target="_blank" rel="noopener">`;`.widget-image__link{display:block}` + hover opacity 0.92。
- 版本 2.4.6→2.4.7。

### 坑 / 注意
- 间距收紧后中间栏不再与侧栏 `.widget`(仍 1.25rem)顶/底对齐;如需同步,改 `.widget` padding。
- 默认图 contain 左右留 ~12.5% 黑边,靠 `background:#000` 与图底同色消割裂;换非黑底默认图需同步改 background。
- `onedong.zip` 沿用历史策略,不纳入提交(`git checkout` 恢复)。

## v2.5.0(2026-06-29)· 朋友圈模块(onedong_moment)+ 作者头像自定义上传 + 文章卡去边距

### 新增:朋友圈(独立模块 `inc/moments.php`)
- **CPT** `onedong_moment`(slug `moments`,访问 `/moments/`),`dashicons-format-status`,菜单位 6;`show_in_rest=false`(经典 meta box 发布)。首次注册 + 切换主题各 flush 一次固定链接(防 404)。
- **后台发布**(wp-admin → 朋友圈 → 发布):正文框写文字;meta box「图片与定位」= 多图上传(WP Media Frame 多选,最多 9,缩略图预览 + × 移除)+ 定位文本。nonce 安全保存;图片仅存 attachment ID、去重、限 9。
- **前端**:`archive-onedong_moment.php` / `single-onedong_moment.php` 调 `onedong_render_moment()`,微信朋友圈流(圆形头像 + 昵称主色 + 文字 + 图片 + 定位 + 相对时间)。
- **图片展示**:1 张=大图(`max-width:68%`),2–9 张=九宫格(3 列 `aspect-ratio:1/1`,`onedong-moment-thumb` 300×300 正方形尺寸)。点击图片 lightbox 全屏 + 左右切换 + ESC。
- **资源**:`assets/css/moments.css` + `assets/js/moments.js`(lightbox,零依赖);后台 `assets/js/moment-admin.js` + `assets/css/moment-admin.css`(仅 CPT 编辑页加载)。条件 enqueue(archive/singular)。
- 入口:后台左侧菜单「朋友圈」发布;访问 `/moments/` 查看。导航菜单需在「外观→菜单」手动加 `/moments/` 链接。

### 新增:作者头像自定义上传
- `onedong_avatar_source` choices 加 `custom`(自定义上传),sanitize 白名单同步;新增 `onedong_avatar_custom`(WP_Customize_Image_Control)。
- `sidebar-left.php` 作者卡加 `custom` 分支:`<img class=widget-profile__avatar src=上传图>`(复用现有头像样式)。

### 改动:文章卡去边距(TD 在 v2.4.7 基础上再要求贴边)
- `.post-card__body` padding-top `0.75rem → 0`(头像贴卡片顶)。
- `.post-card__stats` padding-bottom `0.75rem → 0`(点赞行贴卡片底)。
- ⚠️ 中间栏内容比侧栏 widget 更贴边(侧栏仍 1.25rem);如要统一再调 `.widget`。

### 版本
- ONEDONG_VERSION + style.css `2.4.7 → 2.5.0`(刷资产缓存)。

### 坑 / 注意
- **九宫格需回填缩略图**:`onedong-moment-thumb`(300×300)是新尺寸;已上传的图需跑 Regenerate Thumbnails 才有正方形缩略图,否则 WP 用原图(九宫格仍正常,流量略大)。
- **CPT 固定链接 404**:首次访问 `/moments/` 若 404,后台「设置→固定链接」重保存一次(`onedong_moment_flushed` 已自动 flush,通常无需)。
- **无本地 PHP**:函数/模板语法已人工核对;本机无 php -l,建议本地 WP 启用后用 Theme Check 复查。
- 定位为纯文本地点名;未来若要地图选点需接高德/百度地图 API(本期未加经纬度字段)。
- `onedong.zip` 沿用历史策略,不纳入提交。

## v2.5.1(2026-06-29)· 朋友圈点赞 + 分享(「••」折叠,纯图标气泡)

### 改动(`inc/moments.php` + `assets/css/moments.css` + `assets/js/moments.js` + `functions.php`)
- **操作按钮**:朋友圈每条动态右下角加「••」(两个小圆点),点击展开微信风深灰气泡(`#333` 圆角 8px + 顶部小三角指向按钮)。
- **纯图标**:气泡内横向两个图标按钮——赞(心形)+ 分享(纸飞机),竖线分隔(参考微信朋友圈)。TD 明确要「点赞跟分享用图标展示」,无文字。
- **点赞**:复用文章卡点赞机制(REST `/onedong/v1/like` + `_onedong_likes` meta);**放宽 REST `validate_callback`**:`post_type === 'post'` → 允许 `post` 与 `onedong_moment`。localStorage 防重复,已赞心形变红 `#ff3b5c`。
- **分享**:`navigator.share`(移动端原生分享面板)→ 降级复制链接到剪贴板(桌面)。
- 设置来源:`window.onedongLike`(likes.js 全站 localize);回退读 `.moment__actions` 容器 `data-like-url`/`data-nonce`(防 likes.js 未加载)。
- 版本 2.5.0→2.5.1。

### 坑 / 注意
- 点赞数不在气泡里显示(微信气泡是纯赞按钮,赞过的人列表在动态下方,本期未做点赞列表);如需显示「❤ N」可读 `_onedong_likes`。
- 分享在桌面浏览器无 `navigator.share`,走「复制链接」(无提示);如需 toast 反馈后续加。
- `onedong.zip` 沿用历史策略,不纳入提交。

## v2.5.2(2026-06-29)· 朋友圈三栏布局(左作者卡 + 中流 + 右侧栏)

### 改动(`archive-onedong_moment.php` + `single-onedong_moment.php`)
- 朋友圈归档页 / 单条页改为三栏(与首页一致):`site-content--three-col` + `get_sidebar('left')`(左作者卡)+ `.content-main`(朋友圈流)+ `get_sidebar()`(右侧栏)。
- `moments-feed`(max-width 600px)在 content-main 居中,与首页文章流对齐。
- 版本 2.5.1→2.5.2。

### 坑 / 注意
- 左/右侧栏模块沿用首页同一套(后台「外观→自定义→左/右侧栏模块」开关控制)。
- `onedong.zip` 沿用历史策略,不纳入提交。

## v2.5.3(2026-06-29)· 朋友圈中间栏顶部对齐左右栏

### 改动(`assets/css/moments.css`)
- `.moments-page` padding-top `1.5rem → 0`:原顶 padding 致朋友圈流顶部比左右栏低 1.5rem(首页 `.content-main` 无 padding,三栏 grid `align-items:start` 顶部对齐;朋友圈加 `.moments-page` 后顶 padding 破坏对齐)。底部 padding 3rem 保留。
- 版本 2.5.2→2.5.3。

## v2.5.4(2026-06-29)· 朋友圈点赞心形修复(空心描边 → 点赞实心红)

### 改动(`inc/moments.php` + `assets/css/moments.css`)
- **心形 path 换标准 Material 心形**(原 path 形状变形):`M12 21.35l-1.45-1.32C5.4 15.36…`。
- **默认空心描边**:path `fill="none" stroke="currentColor"`(白色描边空心心,深灰气泡上清晰)。
- **点赞后实心红**:CSS 选择器从 `.moment__pop-icon`(svg,靠继承)改为 `.moment__pop-icon path`(直接选 path),`fill:#ff3b5c; stroke:#ff3b5c`。
- 版本 2.5.3→2.5.4。

## v2.5.5(2026-06-29)· 朋友圈分享改为卡片海报(图+头像+文字+二维码,可保存图片)

### 改动(`functions.php` + `assets/js/moments.js` + `assets/css/moments.css`)
- **分享卡片**:点分享按钮 → 弹海报浮层(替代原 navigator.share / 复制链接):
  - 图片:取该条第一张(`.moment__img` 的 data-full/src);**无图回退默认缩略图** `default-thumb.png`(经 `onedongMomentShare.defaultThumb` 传入)。
  - 作者头像(`.moment__avatar img`)+ 昵称 + 文字(`.moment__content` textContent,4 行省略)。
  - **二维码**:`qrcodejs`(jsdelivr CDN)生成该条 permalink 的码;库加载失败回退在线 API(qrserver.com)。
  - 「保存图片」:`html2canvas`(CDN,useCORS + scale 2)把 `.moment-share__card` 转 PNG 下载。
- **库(仅朋友圈页条件加载)**:`qrcodejs` + `html2canvas`,均 CDN;`wp_localize_script` 注入 `onedongMomentShare`(defaultThumb + siteName)。
- 卡片海报**白底固定色**(不随站点暗色模式),因 html2canvas 对 CSS 变量支持差,固定色保证导出正常。
- 版本 2.5.4→2.5.5。

## v2.5.6(2026-06-29)· 朋友圈后台图片拖拽排序

### 改动(`assets/js/moment-admin.js` + `assets/css/moment-admin.css`)
- 后台发布 / 编辑朋友圈时,多图可**拖拽调整顺序**(原只能按选择顺序)。
- **HTML5 Drag & Drop**(零额外依赖):`.moment-img-item` 设 `draggable`,事件委托在 `.moment-img-list` 上处理 `dragstart/dragover/dragend`;按鼠标在目标项的上/下半决定插前 / 插后;`dragend` 重新 sync hidden input(逗号 ID = 展示顺序)。
- 初始已有图片(PHP 渲染的 li)由 `makeDraggable()` 统一设 draggable;新添加的 li 自带 `draggable="true"`。
- CSS:`.moment-img-item[draggable]{cursor:move}` + `.is-dragging{opacity:.4}`(拖动中半透明)。
- 前端展示顺序即后台排列顺序(`_onedong_moment_images` 数组顺序),九宫格按序渲染。
- 版本 2.5.5→2.5.6。

## v2.5.7(2026-06-29)· 朋友圈点赞数显示 + 点击动效

### 改动(`inc/moments.php` + `assets/js/moments.js` + `assets/css/moments.css`)
- **点赞数显示**:赞按钮(气泡内)心形旁加 `<span class="moment__pop-num">N</span>`,读 `onedong_get_likes()`(朋友圈 CPT 复用 `_onedong_likes`)。
- **点击成功动效**:
  - 心形 `moment-heart-pop` 动画(scale 1→1.45→0.9→1,回弹 0.4s)。
  - 飘心:按钮位置生成 `<span class="moment__fly-heart">❤</span>`,`moment-fly` 向上飘 200% + 渐隐(0.75s),结束移除。
- JS:REST 成功后数字 +1(`moment__pop-num` textContent)+ 触发 `flyHeart()` + is-liked(变红)。
- `.moment__pop-btn--like` 改 width auto + padding(容纳心 + 数字)+ position relative(飘心定位)。
- 版本 2.5.6→2.5.7。

## v2.5.8(2026-06-29)· hover 改图标变色(去背景)+ 图标缩放动效

### 改动(`assets/css/layout.css` + `assets/css/moments.css`)
- **首页文章卡点赞爱心**(`.post-card__like`):`.icon` 加 `fill: currentColor`(原 fill 默认黑不跟随 color,hover 爱心其实没变红);hover 爱心变红(`color:#ff3b5c`)+ 图标 `scale(1.2)` 动效。无背景变色。
- **朋友圈「••」圆点**(`.moment__toggle`):hover 圆点变主色 `var(--primary)`(原 text-faint 偏弱)。无背景。
- **朋友圈气泡赞 / 分享按钮**(`.moment__pop-btn`):去掉 hover 背景 `#444`,改为图标 hover `scale(1.18)` 动效;赞按钮 hover 图标预览红 `#ff3b5c`。
- 版本 2.5.7→2.5.8。

## v2.5.9(2026-06-29)· 补全朋友圈 hover 图标变色(v2.5.8 漏改 moments.css)

### 改动(`assets/css/moments.css`)
- v2.5.8 提交时 moments.css 因 linter 改动未一并进入该 commit,本版补上:
  - `.moment__toggle:hover .moment__dot` 圆点 hover 变主色 `var(--primary)`(原 text-faint 偏弱)。
  - `.moment__pop-btn:hover` 去掉背景 `#444`,改为图标 hover `scale(1.18)` 动效;赞按钮 hover 图标预览红 `#ff3b5c`。
- 版本 2.5.8→2.5.9。

## v2.5.10(2026-06-29)· 点赞/分享 hover 去背景;爱心 hover 变实心红(参考阅读 stat 简洁变色)

### 改动(`assets/css/layout.css` + `assets/css/moments.css`)
- **统一去 hover 背景**(TD 反复要求):`.moment__toggle:hover` / `.moment__pop-btn:hover` 显式 `background:transparent`(本就 none,防御性 + 消除旧部署残留)。
- **首页爱心 hover 变实心红**(参考文章阅读 stat 的简洁变色,去掉之前的 scale 放大):`.post-card__like:hover .icon { fill:#ff3b5c }`。
- **朋友圈气泡**:分享按钮 hover 保留 `scale(1.18)` 动效;爱心 hover 改实心红(path fill+stroke #ff3b5c,无 scale)。均无背景。
- 版本 2.5.9→2.5.10。

### 坑 / 注意(关键)
- `onedong_get_icon` 所有图标 SVG 是 `fill:none stroke:currentColor`(Feather 描边风),默认**空心**;要"实心"必须 CSS 显式 `fill`(currentColor 或具体色)覆盖 svg 的 `fill="none"` presentation attribute。
- TD 多轮反馈"有背景",主因是部署版本滞后(v2.5.8 commit 漏了 moments.css,v2.5.9 补);本版再显式 `transparent` 兜底。**部署后务必强刷(Ctrl+F5)清 CSS 缓存**,因 CSS 资源 URL 带版本号(bump 到 2.5.10 会自动破缓存)。

## v2.5.11(2026-06-29)· 朋友圈实况图片(Live Photo)+ 首页点赞 hover 去背景兜底

### 改动(`inc/moments.php` + `assets/js/moment-admin.js` + `assets/css/moments.css` + `assets/js/moments.js` + `assets/css/layout.css`)
- **实况图片(Live Photo)**:每张图可选配一段视频(媒体库 type=video)。
  - 后台 meta box:图片 li 加「实况」按钮(WP Media 选视频),存 `_onedong_moment_live`(img_id => video_id 配对),`#moment-live` hidden(JSON)。
  - 前端 `onedong_render_moment`:配了视频的图包 `.moment__live-wrap`(relative)+ img 加 `data-video` + `.moment__live-badge`「实况」角标。
  - moments.js:`.moment__img[data-video]` 悬停(桌面 mouseenter)/ 长按(移动 touchstart)→ 叠加 `<video muted loop>` 播放,离开移除。
  - CSS:角标(左下半透明黑底白字)+ video absolute 覆盖 img。
- **首页点赞 hover 去背景兜底**:`.post-card__like:hover` 显式 `background: transparent`(v2.5.10 已实心红,本版兜底防部署残留)。
- 版本 2.5.10→2.5.11。

### 坑 / 注意
- 实况视频需 WP 媒体库允许视频上传(默认允许 mp4/mov 等);视频较大注意服务器 `upload_max_filesize`。
- hover 播放依赖浏览器自动播放策略,**`muted` 必填**(否则被拦截不播)。
- 实况图点击仍触发 lightbox 看大图(与 hover 播放不冲突:一个 hover 一个 click)。

## v2.5.12(2026-06-29)· 主题切换去电脑图标改二态 + 文章卡日期移到昵称下

### 改动(`header.php` + `assets/js/theme-toggle.js` + `assets/css/layout.css` + `template-parts/content.php`)
- **主题切换二态**:去掉 auto / monitor(电脑)图标,只留日(light)/ 月(dark);点击 light↔dark 切换,localStorage 记忆;无记忆时跟随系统。`theme-toggle.js` 重写为二态;`header.php` 去 monitor span;CSS 去 `[data-pref=auto]` 规则。
- **文章卡日期下移**:作者列改为 头像 + `.post-card__author-info`(昵称 + 日期竖排),日期从 meta 右侧独立位置移到昵称下方。CSS `.post-card__author-info{flex-direction:column}`,日期小字 0.72rem。
- 版本 2.5.11→2.5.12。

### 坑 / 注意
- ⚠️ **线上 dingxudong.com 跑的是 Once-main 主题(非 onedong)**(webReader 确认 stylesheet 是 `themes/Once-main/`),moments / 点赞动效 / 实况等 onedong 独有功能线上看不到;验收需把 onedong 部署启用(后台「外观→主题」切换,或 scp 覆盖后启用)。
- 去掉 auto 后失去「跟随系统实时变化」(系统切换主题 onedong 不自动跟),但用户可手动点切。

## v2.5.13(2026-06-29)· 文章卡日期去图标 + 年-月-日格式

### 改动(`template-parts/content.php`)
- 日期去掉前置 calendar 图标,改 `Y-m-d`(年-月-日,如 `2026-06-29`)纯文本展示。
- 版本 2.5.12→2.5.13。

## v2.5.14(2026-06-29)· 性能优化(禁用 emoji + 清理 head + Prism 条件加载 + preconnect)

### 改动(`functions.php`)
- **禁用 WP emoji**:移除 emoji 检测脚本 + 样式(省 1 个 JS 请求;主题图标已全用内联 SVG)。
- **清理 head**:移除 RSD / WLW / generator / shortlink 多余 meta。
- **Prism 代码高亮条件加载**:仅文章详情页(`is_singular('post')`)加载,列表 / 首页省 3 个 CDN 请求(CSS + core + autoloader)。
- **资源预连接(preconnect)**:gravatar(头像)+ jsdelivr(Prism),提前建立连接。
- 版本 2.5.13→2.5.14。

### 图片加载现状(此前已优化,本版未改)
- 文章卡封面:`wp_get_attachment_image` + srcset/sizes + 首屏 `eager`+`fetchpriority=high`(LCP)+ 非首屏 `loading=lazy`。
- 朋友圈图片:`loading=lazy` + `decoding=async`;九宫格用 `onedong-moment-thumb`(300×300)正方形小图,单图用 large。
- 默认缩略图 default-thumb.png(4:3)。

### 后续建议(需服务器 / 插件,主题层无法做)
- **WebP/AVIF**:装 WebP Express / Imagify 等插件自动转下一代格式(更小);或上传时直接传 WebP(WP 5.8+ 支持)。
- **对象缓存**:Redis / Memcached(动态查询加速)+ 页面缓存(WP Super Cache / W3 Total Cache)。
- **CDN**:Cloudflare 等缓存静态资源 + 图片。
- 跑一次 Regenerate Thumbnails 回填 `onedong-card` / `onedong-moment-thumb` 新尺寸缩略图(老图才有正方形 / 4:3 缩略图)。

## v2.5.15(2026-06-29)· WebP 自动转换 + 前端 picture 优先

### 改动(`functions.php`)
- **上传时自动转 WebP**:hook `wp_generate_attachment_metadata`,为原图 + 各尺寸(含 onedong-card / onedong-moment-thumb)生成 `.webp` 副本(GD `imagewebp`,质量 82);转成功记 `_onedong_has_webp` meta。PNG 保透明(imagealphablending + imagesavealpha)。
- **前端 picture 优先**:filter `wp_get_attachment_image`,有 webp 的图包装成 `<picture><source type=image/webp srcset=…webp><img …></picture>`,浏览器支持 WebP 用 webp(更小),否则 fallback 原图。
- 仅 image/jpeg|png|gif;依赖 PHP GD 的 `imagewebp`(不支持自动跳过,无副作用)。
- 版本 2.5.14→2.5.15。

### 坑 / 注意
- **需 PHP GD WebP 支持**(`imagewebp`,PHP 编译 --with-webp);多数主机有;无则不转换、不 picture,降级原 JPG。
- **老图**:需重新生成缩略图才转 WebP(后台跑 Regenerate Thumbnails,或重新上传;hook 在 `wp_generate_attachment_metadata` 触发)。
- WebP 副本文件名 = 原名 + `.webp`(如 `img-300x200.jpg.webp`),与原图同目录;前端 url 同样 `+ '.webp'`。
- GIF 转 WebP 只取首帧(不动画);动态 GIF 建议保留原 GIF(本方案仍转,如需排除 GIF 改白名单)。
- 与缓存插件(WP Super Cache 等)兼容;picture 由 `wp_get_attachment_image` filter 动态生成,缓存的是带 picture 的 HTML。

## v2.5.16(2026-06-29)· WebP 转换优先 Imagick(GD 回退)

### 改动(`functions.php`)
- `onedong_webp_convert` 改为**优先 Imagick**(`new Imagick` + `setImageFormat('webp')` + 质量 82),**GD `imagewebp` 回退**。Imagick 质量更好且 WebP delegate 通常自带。
- `onedong_make_webp` 入口检查:`imagewebp` 或 `Imagick` 任一可用即转(不再只看 GD)。
- 先确认 Imagick 支持 WEBP(`queryFormats` 含 WEBP)才用,否则落 GD。
- 版本 2.5.15→2.5.16。

### 坑 / 注意
- TD 服务器已装 Imagick → WebP 转换走 Imagick(无需 GD WebP 编译)。
- Imagick 写 webp 失败(异常)自动落 GD;两者都无则跳过(降级 JPG,无副作用)。

## v2.5.17(2026-06-29)· 文章卡字数图标更换(type T → 文字行)

### 改动(`functions.php`)
- 字数图标(`onedong_get_icon('type')`)从 feather "type"(T 字)换为**文字行**(align-left 风格:三横线,表示文字/字数,更直观)。
- 版本 2.5.16→2.5.17。

## v2.5.18(2026-06-29)· 朋友圈流左对齐中间栏(对齐顶部导航)

### 改动(`assets/css/moments.css`)
- `.moments-feed` `margin: 0 auto`(居中)→ `margin: 0`(左对齐 content-main)。朋友圈流左边缘对齐中间栏左边 = 顶部导航 nav 左边(同列对齐)。
- 版本 2.5.17→2.5.18。

### 说明
- 中间栏(content-main)左边在 grid 中列,本身已与顶部导航 nav(同中列)左边对齐;此前朋友圈流 `margin:0 auto` 居中导致视觉偏右,左边缘没对齐 nav。改左对齐后即对齐。
- 若想朋友圈流对齐到顶部导航 brand(最左列),需调整 grid(让朋友圈页去掉左栏或 content-main 跨列),本期未做。

## v2.5.19(2026-06-29)· Logo 双风格(浅色 + 暗色模式浅色)+ 自动切换 / 反色

### 改动(`functions.php` + `header.php` + `assets/css/layout.css`)
- **暗色 Logo 上传**:新 customizer section「品牌 / Logo」+ 设置 `onedong_logo_dark`(WP_Customize_Image_Control)。浅色 Logo 仍用「站点身份」(custom_logo)。
- **header.php**:`site-brand` 有暗色 Logo 时加 class `site-brand--has-dark`,输出 `<img class="site-logo--dark">`(暗色 Logo)。
- **自动切换**(CSS):
  - 无暗色 Logo:深色模式浅色 Logo `filter: invert(1)`(自动反色,适纯色 Logo)。
  - 有暗色 Logo:深色模式隐藏浅色、显示暗色(精确双风格)。
- 版本 2.5.18→2.5.19。

### 坑 / 注意
- `filter: invert(1)` 仅对纯黑 / 白 Logo 效果好;彩色 Logo 建议上传专门的暗色 Logo(浅色版)。
- 浅色 Logo 用 WP「站点身份」(custom_logo);暗色 Logo 用「外观→自定义→品牌 / Logo」。

## v2.5.20(2026-06-29)· 主题介绍文案更新

### 改动(`style.css`)
- Description 改为 TD 指定:「OneDong 主题,删繁去冗,以极简线条、留白美学重构视觉秩序,克制设计、聚焦内容,用最少元素,呈现最高级的表达。」
- 版本 2.5.19→2.5.20。

## v2.5.21(2026-06-29)· Logo 适量缩小展示

### 改动(`assets/css/layout.css`)
- `.site-brand img` 限制 `max-height: 38px` + `max-width: 180px`(width/height auto 保持比例),避免上传的 logo 过大撑破顶栏;移动端 `max-height: 32px` / `max-width: 150px`。
- 版本 2.5.20→2.5.21。

### 坑 / 注意
- 高度优先(横版 logo 按高度缩,竖版按高度缩);`max-width` 防超宽横 logo 溢出。如需更大 / 更小改这两个值即可。

## v2.5.22(2026-06-29)· 中间栏头像顶部对齐左栏头像

### 改动(`assets/css/layout.css`)
- `.post-card__body` padding-top `0 → 1.25rem`,中间文章卡头像顶部对齐左栏作者卡头像顶部(左栏 `.widget` padding-top 1.25rem)。
- 三栏 grid `align-items:start` 各栏顶部对齐;此前中间 body padding-top 0 致中间头像比左栏头像高 1.25rem。
- 版本 2.5.21→2.5.22。

### 坑 / 注意
- 移动端(≤640px)左栏隐藏,`.post-card__body` padding-top 保持 0.9rem(不需对齐左栏)。
- 这与 v2.5「去顶边距(贴顶)」相反;TD 改主意要头像对齐左栏,故加回 1.25rem。

## v2.5.23(2026-06-29)· 中间栏头像微调上移对齐左栏

### 改动(`assets/css/layout.css`)
- `.post-card__body` padding-top `1.25rem → 1rem`,中间头像上移 ~4px 对齐左栏作者卡头像。
- 根因:左栏作者卡是大头像(96px,`inline-block`+`line-height:0`)贴 widget 内容区顶;中间是小头像(40px)在 `.post-card__author`(`align-items:center`)里,旁边 `author-info`(昵称+日期两行≈40px)让小头像被 flex 居中挤下,实测低 5-10px。减小中间顶 padding 补偿。
- 版本 2.5.22→2.5.23。

### 坑 / 注意
- 若仍偏差,可改 `.post-card__author { align-items: flex-start }`(头像顶对齐 author 顶,不居中),但会影响头像/昵称视觉关系;本期先用 padding 微调。

### 坑 / 注意
- SVG `.icon` 的 `fill` 默认不跟随父级 `color`;要图标随 hover 变色必须显式 `fill: currentColor`(本次 `.post-card__like .icon` 的关键修复,否则 hover 只变文字色、爱心图标本身不变红)。

### 坑 / 注意
- 赞数来自 `_onedong_likes` meta(与文章卡点赞同一字段、同一 REST);朋友圈与文章赞数各自独立(不同 post_id)。
- 飘心是 REST 成功后触发;若网络慢,用户点击后略迟才有动效(可改乐观更新,本期未做)。

### 坑 / 注意
- HTML5 DnD 在**触屏**(手机/平板)不工作;后台是 PC 操作,够用。日后若要前端触屏排序,需换 SortableJS 之类带触屏支持的库。

### 坑 / 注意
- **html2canvas 跨域**:头像若来自 gravatar(跨域),需 `crossorigin="anonymous"` + `useCORS:true`;gravatar 有 CORS 头一般 OK;若自定义头像源无 CORS,保存的图头像可能空白(浮层展示不受影响)。
- **二维码库**:qrcodejs 走 jsdelivr CDN,国内通常可访问;失败回退 qrserver.com(可能慢)。
- 「保存图片」依赖 html2canvas 成功渲染;若浏览器拦截跨域图片,可改用截屏。

### 坑 / 注意(关键)
- SVG `fill` 作为 `<path>` 的 **presentation attribute**,优先级**低于** author CSS,但**高于**从父级 `<svg>` 继承的 CSS 值。故「点赞实心红」必须**直接选 path 元素**设 fill,不能靠 svg 继承(否则 path 自带 `fill="none"` 胜出,心填不红)。

## v2.5.24(2026-06-29)· 文章卡「紧凑 + 呼吸」间距重设计

### 背景
- TD 此前手改 `.post-card__body` / `.post-card__stats` padding 为 `0`(内容贴边)后发现:封面图 `.post-card__thumb` 仍 `margin:0 1.1rem` 缩进 → **文字/数据行贴边 vs 封面缩进,三者左边不对齐**,且贴边无呼吸。
- 取向:**紧凑 + 呼吸感** = 介于「贴边(0)」与「旧版(1.25rem 太松)」之间的甜点区;左右统一 `1.1rem` 与封面缩进对齐成一线。

### 改动(`assets/css/layout.css`)
- **外内边距对称(两轮收紧)**:`.post-card__body` `padding:0.5rem 1.1rem 0.4rem` + `.post-card__stats` `padding:0.4rem 1.1rem 0.5rem`(外 0.5 / 内 0.4 / 左右 1.1 对齐封面缩进)。TD 初版要「呼吸」(0.8/0.65),后反馈上下边距仍偏大,再压到 0.5/0.4。垂直节奏:卡顶 0.5 → 作者/标题/摘要 → 0.4 → 封面 → 0.4 → 数据行 → 0.5 → 卡底。
- **内部元素收紧**:`.post-card__meta` margin-bottom `0.5→0.35rem`、`.post-card__title` `0.5→0.35rem`、`.post-card__summary` `0.35→0.22rem`。
- **移动端**(`≤640px`):`.post-card__body` `0.9rem 1rem 1rem → 0.55rem 1.1rem 0.45rem`。
- **封面保持缩进对齐**:TD 一度要全宽贴边(`margin:0`)后改回原版 `margin:0 1.1rem`(缩进 + 自成圆角,与文字/数据行 1.1 对齐)。
- 版本 2.5.23→2.5.24(`style.css` + `ONEDONG_VERSION`,刷 CSS URL 缓存)。

### 坑 / 注意
- 「呼吸感」靠**非零外边距**(0.8rem 卡顶/卡底)保证,「紧凑」靠**内部元素间距 + 封面两侧 0.65rem**;调甜点区改这四个值即可(外 / 内 / 左右)。
- 左右 `1.1rem` 与 `.post-card__thumb{margin:0 1.1rem}` 必须一致,否则作者/标题/摘要/封面/数据行左边错位(v2.4.2 立的「缩进对齐」原则)。
- 字体大小未动(头像 2.5rem / 标题 clamp / 摘要 0.92rem 沿用);「紧凑」只动间距,不动字号。
- ⚠️ 线上仍跑 Once-main;部署启用 OneDong + 刷腾讯云 CDN 后生效。`onedong.zip` 沿用历史策略,不纳入提交。

## v2.5.25(2026-06-29)· 文章卡上下边距参考朋友圈(放松)

### 背景
- **线上 dingxudong.com 已部署 onedong v2.5.24**(webReader 确认 stylesheet=`themes/onedong/...?ver=2.5.24`)—— DEV_NOTES 此前「线上跑 Once-main」记录已过期,现 OneDong 已启用并生效。
- TD 部署后对比:首页文章卡(v2.5.24 收紧到外 0.5rem)与朋友圈卡(`.moment` 上下 0.95rem)呼吸不一致,要求**文章卡上下边距参考朋友圈调整**。
- 取舍:朋友圈 `.moment` 上下 padding = `0.95rem`(TD 未抱怨,作基准)→ 文章卡外边距放松回 `0.9rem`(≈朋友圈),内边距(图片↔文字/数据)`0.55rem`。

### 改动(`assets/css/layout.css`)
- `.post-card__body` `0.5rem 1.1rem 0.4rem → 0.9rem 1.1rem 0.55rem`(外 0.9≈朋友圈 / 内 0.55)。
- `.post-card__stats` `0.4rem 1.1rem 0.5rem → 0.55rem 1.1rem 0.9rem`(内 0.55 / 外 0.9≈朋友圈)。
- 移动端 `.post-card__body` `0.55rem 1.1rem 0.45rem → 0.85rem 1.1rem 0.55rem`。
- 版本 2.5.24→2.5.25(`style.css` + `ONEDONG_VERSION`,刷 CSS URL 缓存)。

### 坑 / 注意
- **方向与 v2.5.24 相反**:v2.5.24 TD 要求「缩减不要空白」收到 0.5rem;部署后实测偏紧,改参考朋友圈(0.95rem)放松到 0.9rem。**甜点值随实测反复**,后续若再调改这四个值(外/内/左右)。
- 朋友圈 `.moment` 未动(TD:「朋友圈的不用改」),作基准保留 `0.95rem`。
- `onedong.zip` 沿用历史策略,不纳入提交。

## 文章详情页底部互动栏(浏览/字数/评论/点赞)· 2026-07-02

### 背景
- TD 要求把文章列表卡底部的「浏览 / 字数 / 评论 / 点赞」4 项统计,也放到**文章详情页(`single.php`)底部**(TD 原话「商品详情页」,实指文章单篇页——本主题无商品 CPT,唯一详情页即 single.php)。

### 改动
- `single.php`:标签块之后、`</article>` 之前新增 `.entry-actions` 互动栏。
  - **4 项平级**(无内层包裹):阅读 `onedong_get_views()`(eye)/ 字数 `onedong_word_count()`(type)/ 评论 `get_comments_number()`(chat)/ 点赞 `onedong_get_likes()`(heart)。
  - 点赞按钮**沿用 `.post-card__like` 类**(`data-id` + 内部 `.count`)→ `likes.js` 已全站加载且写死绑定该选择器,**详情页点赞直接可用,无需改 JS**。
- `assets/css/layout.css`:新增 `.entry-actions` —— **四项单行均布**:`flex:1 1 0` + `justify-content:center` 让 4 项等宽居中排成一行,`flex-wrap:nowrap` + `white-space:nowrap` 锁死单行(永不换行),顶部分隔线;`≤480px` 缩字号。复用 `.post-card__stat` / `.post-card__like` 样式。
  - 初版是「左 3 统计 + 右点赞」两组(`.entry-actions__stats` 包裹),TD 反馈要「一行展示」→ 改为 4 项平级均布(去掉包裹层)。

### 坑 / 注意(关键)
- **点赞按钮必须用 `.post-card__like` 类**(不要新建类):`likes.js` 写死 `querySelectorAll('.post-card__like')`,且靠内部 `.count` 节点回填点赞数。换别的类名 → 详情页点赞点不动。
- `.post-card__footer` **不能直接套用**:它带卡片专属 `margin-left:3.6rem`(给头像让位),详情页用会错位。故只复用 `__stat` / `__like`,外层另起 `.entry-actions`。
- 统计项与列表卡 `content.php` 同数据源、同显示规则(均**不挂** Customizer 开关,无条件显示)。
- ⚠️ **CSS 改了但版本号未 bump**(当前 `6.0.4-ProMax`):线上 CDN / 浏览器会吃旧 CSS 缓存,部署前需 bump `style.css` + `ONEDONG_VERSION` 刷 URL(沿用既定策略)。

## v6.0.5(2026-07-02)· 详情页底部改版(分享/阅读/评论/点赞)+ 分享卡片

### 背景
- TD 实测定稿:底部由「阅读 / 字数 / 评论 / 点赞」改为 **「分享 / 阅读 / 评论 / 点赞」** —— 去字数,首位换「分享」(触发分享卡片)。「阅读」= 阅读量(浏览数 eye),非阅读时长。
- 分享卡片要求:作者头像 + 昵称 + 文章标题 + 文章介绍 + 二维码。复用朋友圈同款 qrcodejs(生码)+ html2canvas(存图)。

### 改动
- `functions.php`:
  - `onedong_get_icon` 新增 `share` 图标(三节点连线,Feather share)。
  - 新增 `onedong_share_card()`:服务端渲染隐藏浮层 `.post-share`(头像[加 crossorigin] + 昵称 + 站点 + 标题 + 简介[mb_substr 截 80 字] + 二维码容器[data-url=permalink] + 保存/关闭按钮)。
  - `is_singular('post')` 下 enqueue `onedong-qrcode` / `onedong-html2canvas`(CDN,与朋友圈同 handle)+ 新 `share.js`(依赖前两者),localize `onedongPostShare{saveText,busyText}`。
- `single.php`:`.entry-actions` 四项改为 **分享(`<button class="entry-share" data-share-trigger>`)/ 阅读(`post-card__stat` eye)/ 评论(`post-card__stat` chat)/ 点赞(`post-card__like`)**;`</article>` 后调用 `onedong_share_card()`。
- `assets/js/share.js`(新增):点 `[data-share-trigger]` → 开 `.post-share` → qrcodejs 据容器 `data-url` 生码(失败回退 qrserver API,只生一次)→ `[data-share-close]` / Esc 关 → `[data-share-save]` 用 html2canvas 截 `#postShareCard` 存 png(文件名取标题)。
- `assets/css/layout.css`:`.entry-actions` 改用 `> *` 让四项等宽居中;新增 `.entry-share` 按钮(reset + hover 转 primary);新增 `.post-share` 浮层 + 卡片全套样式(对齐朋友圈分享卡,**固定 hex 色**保 html2canvas 导出)。

### 坑 / 注意(关键)
- **分享按钮独立类 `.entry-share`**(非 `.post-card__like`):点赞才用 `.post-card__like`(likes.js 绑定 + 红 hover);分享要蓝 hover,故另起类。两者都是 `.entry-actions` 直接子节点,靠 `> *` 统一布局。
- **头像必须 `crossorigin="anonymous"`**:否则 html2canvas 截 gravatar 会污染 canvas → `toDataURL` 抛错,存图失败。已在 `get_avatar` 的 `extra_attr` 加。
- **卡片色用固定 hex**(#1d2129 / #4e5969 / #86909c / #f2f3f5),不用 CSS 变量 —— 对齐朋友圈分享卡策略,保 html2canvas 导出稳定。
- 二维码走 qrcodejs 本地生 canvas(同源,html2canvas 安全);库未加载时回退 qrserver 远程图(可能 CORS,但 save 有 `.catch` 兜底,不致命)。
- ⚠️ **线上 dingxudong.com 实测仍跑 Once-main 不是 OneDong**(webReader 抓取确认 stylesheets 全是 `/themes/Once-main/`)。本改动在 OneDong,部署 OneDong 替换 Once-main 后才生效。旧条「已部署 onedong v2.5.24」与现状不符。
- 版本 6.0.4→6.0.5-ProMax(`style.css` + `ONEDONG_VERSION`,刷 CSS/JS URL 缓存)。

## v6.0.6(2026-07-02)· 修分享按钮「默认选中」+ 下方布局乱

### 背景
- TD 反馈:详情页底部「分享」**默认就是选中态(蓝底)**,且**分享下方布局乱**。
- 抓线上确认:首页 HTML 仍引用 `layout.css?ver=6.0.4-ProMax`(不是 6.0.5),而 `style.css` 头已是 6.0.5 —— 即 **`functions.php` 的 `ONEDONG_VERSION` 没跟着部署**,`?ver=` 没变 → 浏览器/CDN 仍吃**旧 6.0.4 CSS**(里面没有 `.entry-share` / `.post-share` 规则)。
  - 旧 CSS 下 `.entry-share` 按钮只命中全局 `button { background:主色; color:#fff }` → **蓝药丸 = 「默认选中」**;
  - 旧 CSS 下 `.post-share` 没有 `display:none` → **卡片默认展开撑乱下方**。

### 改动(`assets/css/layout.css` + 版本 bump)
- `.entry-share` **显式压过全局 button**:补 `padding / border-radius:0 / box-shadow:none / color:text-faint / font-size`,确保即便旧缓存命中全局 button 也不变蓝(双保险)。
- hover 高亮**包进 `@media (hover: hover)`**:避免触屏点按后 `:hover` 粘住误显「选中」;加 `:focus-visible` 键盘焦点环。
- 版本 6.0.5→6.0.6-ProMax(`style.css` + `ONEDONG_VERSION`)—— **关键:必须部署 `functions.php`**,否则 `?ver=` 不变,缓存照旧。

### 坑 / 注意(关键)
- ⚠️ **版本号在 `functions.php` 的 `ONEDONG_VERSION`,不在 `style.css`**。WP 后台显示的主题版本读 `style.css` 头,但 enqueue 的 `?ver=` 读 `ONEDONG_VERSION`。两者不同步 → 后台显示 6.0.5、线上仍 `?ver=6.0.4` → 浏览器吃旧 CSS。**改完 CSS 必须一起部署 `functions.php`**。
- 部署后还需**刷腾讯云 CDN + 浏览器硬刷新**,否则边缘节点仍回旧 HTML/CSS。

## v6.0.7(2026-07-02)· 详情页分享按钮去文字(仅留图标)

### 背景
- TD 要求详情页底部「分享」按钮**保留 share 图标、去掉「分享」二字文字**(纯图标更简洁)。线上参照 https://dingxudong.com/169.html。

### 改动(`single.php` + 版本 bump)
- `.entry-share` 按钮删除 `<span>分享</span>` 文字节点,**保留** `onedong_icon('share')` 图标 + `aria-label="分享"`(无障碍:屏幕阅读器仍读「分享」)。
- 版本 6.0.6→6.0.7-ProMax(`style.css` + `ONEDONG_VERSION`,刷 CSS/JS URL 缓存)。

### 坑 / 注意
- **CSS 无需改**:`.entry-actions > *` 是 `flex:1 1 0` + `justify-content:center`,四项等宽居中;分享项去文字后只剩图标,图标在等宽格内自动居中,布局不错位。
- **`aria-label` 必须保留**:纯图标按钮若无文字/aria-label,屏幕阅读器读不出用途。
- ⚠️ 本次改的是 PHP(HTML 结构),bump `ONEDONG_VERSION` 只刷 CSS/JS 的 `?ver=`;**页面 HTML 改动需刷腾讯云 CDN**(TD 负责)+ 浏览器硬刷新才在线上生效。

## v6.0.8(2026-07-02)· 移动端菜单改左侧滑出抽屉

### 背景
- TD 要求移动端点击汉堡菜单后,菜单从**左侧滑出**(原为顶栏下方下拉展开 `grid-column:1/-1`)。

### 改动(`header.php` + `assets/css/layout.css` + 版本 bump)
- **`header.php`**:`</nav>` 后新增 `<div class="nav-overlay" aria-hidden="true">` 遮罩层;内联 JS 重写为 `set(open)` —— 同步 toggle `.is-open`(nav + overlay)+ `aria-expanded` + `body.overflow`(锁背景滚动);**点遮罩 / ESC / 点菜单链接** 三种关闭方式(点链接先关闭再正常跳转)。
- **`layout.css`**:
  - 桌面端加 `.nav-overlay { display:none }`(仅移动端显示)。
  - ≤768px:`.primary-nav` 由 `display:none` 下拉改为 **fixed 左侧抽屉**(`width:min(80vw,18rem)` + `height:100dvh`(前置 `100vh` 兜底)+ `transform:translateX(-100%)` + `transition:transform .28s`),`.is-open` → `translateX(0)` 滑入;菜单项选择器由 `.primary-nav.is-open ul/li/a` 改为 `.primary-nav ul/li/a`(抽屉常驻,去掉 is-open 前缀),竖排 + 左 padding `1.25rem`。新增 `.nav-overlay` fixed 全屏遮罩(`rgba(0,0,0,.45)` + opacity/visibility 过渡),`.is-open` 显隐。
- 版本 6.0.7→6.0.8-ProMax(`style.css` + `ONEDONG_VERSION`,刷 CSS/JS URL 缓存)。

### 坑 / 注意
- **抽屉常驻 DOM**:`.primary-nav` 移动端始终 `display:block`,靠 `translateX(-100%)` 移出视口而非 `display:none`——否则 transform 过渡动画无效(这是从「下拉 display 切换」改为「平移」的关键)。
- **层级**:抽屉 z-index `1001`、遮罩 `1000`,均高于顶栏 sticky(`50`);抽屉会盖住顶栏(标准 off-canvas 行为),靠**遮罩点击**关闭,不依赖顶栏汉堡按钮。
- **桌面端零影响**:所有改动在 `≤768px` 媒体查询内 + `.nav-overlay` 桌面默认 `display:none`;桌面导航保持顶栏 inline flex。
- **body 滚动锁**:`document.body.style.overflow='hidden'`;iOS Safari 偶有不完全生效,博客场景可接受(后续可改 position:fixed 方案)。
- ⚠️ 本次改 PHP + CSS,部署务必把 `header.php` + `layout.css` + `functions.php`(刷 `?ver=6.0.8`)一起传;上线后刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.9(2026-07-02)· 修移动端遮罩透明穿透 + 背景参考 PC 端顶栏

### 背景
- v6.0.8 后 TD 反馈:移动端菜单展开时**背景透明、点击穿透**,要求**参考 PC 端导航背景**(`--titlebar-bg` 毛玻璃)。

### 根因(关键坑)
- `.site-header` 用 `backdrop-filter: blur(20px)`(PC 端毛玻璃顶栏,layout.css:46)。**`backdrop-filter` 会成为 `position:fixed` 后代的 containing block**。
- v6.0.8 把 `.nav-overlay` 放在 `.site-header__inner` 内 → 它的 `fixed;inset:0` 被限制在**顶栏盒子**(高 ~60px)里,**没覆盖主内容区** → 主区域无遮罩 → 背景透明 + 点击穿透到下层链接。

### 改动(`header.php` + `assets/css/layout.css` + 版本 bump)
- **`header.php`**:`.nav-overlay` 从 `.site-header__inner` 内**移到 `</header>` 之后**(body 直接子级)→ 不再是顶栏后代,`fixed` 相对视口真正全屏。(JS 不变,仍 `querySelector('.nav-overlay')`。)
- **`layout.css`** ≤768px `.nav-overlay`:
  - 背景 `rgba(0,0,0,.45)` 半透明黑 → **`var(--titlebar-bg)` + 同款 `backdrop-filter: saturate(180%) blur(20px)`**(参考 PC 端顶栏导航;毛玻璃模糊底层 = 视觉不透明 + 不穿透)。
  - `z-index` `1000` → **`40`**(盖主内容 `0`、低于顶栏/抽屉 `50`)。
- 版本 6.0.8→6.0.9-ProMax(`style.css` + `ONEDONG_VERSION`)。

### 坑 / 注意(关键)
- **`backdrop-filter` / `filter` / `transform` 祖先会困住后代的 `fixed`**:fixed 弹层(遮罩 / 抽屉 / 模态)**必须放在该祖先之外**(body 级),否则定位与尺寸被限制在该祖先盒子里。本主题顶栏有 backdrop-filter,故遮罩移到 `</header>` 后。
- **抽屉 `.primary-nav` 仍留 header 内**:在顶栏 containing block 内,但因顶栏 `top:0`+左对齐=视口原点、尺寸用视口单位(`100dvh` / `min(80vw,18rem)`),视觉上正常全屏滑出,无需移出(且桌面端导航布局依赖它在顶栏 grid 中列)。仍用实色 `var(--card)` 保菜单文字清晰。
- **z-index 跨 stacking context 比较**:遮罩(body 级,根 z-index 40)与抽屉(顶栏 stacking context 内,顶栏根 z-index 50)比的是**根层级** → 抽屉随顶栏(50)> 遮罩(40)> 主内容(0),顺序正确,遮罩不会盖住抽屉。
- ⚠️ 部署:`header.php` + `layout.css` + `functions.php`(刷 `?ver=6.0.9`)一起传;刷腾讯云 CDN + 手机端硬刷新验证。

## v6.0.10(2026-07-02)· 修移动端**抽屉本身**透明穿透(令牌笔误)

### 背景
- v6.0.9 后 TD 仍反馈移动端菜单**透明穿透**:菜单展开时背后页面内容**清晰透出**、菜单项文字背后不是实色。v6.0.9 只修了**遮罩 `.nav-overlay`**,没修**抽屉 `.primary-nav` 本体**。

### 根因(关键坑)
- `tokens.css` 定义的卡片底色令牌是 **`--card-bg`**(浅 `#fff` / 暗 `#202022`),**全仓只有这一处**;`--card` 令牌**从未定义**(`grep '--card\s*:'` 无任何命中)。
- `layout.css:1796` ≤768px 抽屉 `.primary-nav` 写的是 `background: var(--card)` → 引用未定义变量 → 该声明**失效被忽略** → `background` 回退到默认 `transparent` → 抽屉透明、页面内容穿透。
- 全仓其余 ~20 处卡片背景**全部**正确用 `var(--card-bg)`,只有移动端抽屉这一行笔误。
- ⚠️ v6.0.9 的 DEV_NOTES 笔记(line 866)原话「仍用实色 `var(--card)` 保菜单文字清晰」是**基于错误假设**——以为 `--card` 是有效实色令牌,实则未定义,所以抽屉一直是透明的。遮罩修了、抽屉没修,导致 TD 仍看到穿透。

### 改动(`assets/css/layout.css` + 版本 bump)
- `layout.css:1796` `var(--card)` → **`var(--card-bg)`**(移动端抽屉 `.primary-nav` 实色背景,菜单文字清晰、不再透出底层)。
- 版本 6.0.9→6.0.10-ProMax(`style.css` + `ONEDONG_VERSION`)。

### 坑 / 注意
- **抽屉 vs 遮罩是两个层**:v6.0.9 修了遮罩(body 级 `--titlebar-bg` 毛玻璃),但抽屉本体(`.primary-nav`,header 内、z-index 1001)这行的笔误一直没碰 → 透明残留。两个修独立。
- **未定义 CSS 变量静默失效**:`var(--undef)` 让整条声明作废(不会报错),`background` 退回初始值 `transparent`。排查「莫名透明」优先查变量名拼写 / 是否真定义。
- ⚠️ 部署:只需 `layout.css` + `functions.php`(刷 `?ver=6.0.10`)一起传(`header.php` 无改);上线后刷腾讯云 CDN + 手机端硬刷新验证(重点看菜单面板是否实色、背后内容不再透出)。

## v6.0.11(2026-07-02)· 朋友圈顶部封面(微信风:封面 + 右下角头像/昵称)

### 背景
- TD 要求 `/moments` 朋友圈页面**顶部参考微信朋友圈**:做一个**封面背景图**,头像 + 昵称放在封面**右下角**(头像下沿悬挂出封面、昵称在头像左侧)。此前页面无封面,`.moments-feed` 直接从第一条动态开始。

### 改动
- **`functions.php`**:新增 Customizer 板块「朋友圈」(`onedong_moments` section)+ 图片上传控件 `onedong_moments_cover`(`WP_Customize_Image_Control`,`esc_url_raw`,`refresh`)。后台「外观 → 自定义 → 朋友圈 → 朋友圈封面图」上传。
- **`archive-onedong_moment.php`**:`.moments-feed` 前新增 `.moments-cover`(banner + `.moments-cover__id` 头像/昵称)。
  - **头像/昵称复用左栏作者卡同一身份**(`onedong_avatar_source` theme mod:logo/gravatar/custom + `admin_email` 的 `display_name`),保证封面与侧栏 profile 一致,不另起数据源。
  - 无封面图且无头像时不渲染整个 `.moments-cover`(干净回退)。
- **`assets/css/moments.css`**:`.moments-cover__banner`(`aspect-ratio:16/7` 宽幅 banner,无图时 `--primary→--primary-strong` 渐变兜底,浅/暗随 token);`.moments-cover__id` `position:absolute; right; bottom:-2.25rem` 头像悬挂出 banner 下沿;`.moments-cover__avatar` 圆角方头(`border-radius:14px`,微信风)+ `--card-bg` 描边与 banner/feed 分离;`.moments-cover__name` 白字 + `text-shadow` 压照片保清晰。`≤768/480` 响应式缩头像/banner/位移。
- 版本 6.0.10→6.0.11-ProMax(`style.css` + `ONEDONG_VERSION`)。

### 坑 / 注意
- **头像用圆角方(非圆形)**:全站其余头像是圆(`.widget-profile__avatar` / `.moment__avatar` 均 50%),但**微信朋友圈封面头像是圆角方**——TD 明确要「参考微信朋友圈样式」,故封面单独用 `border-radius:14px`,属不同语境不冲突。若 TD 要圆改这一处即可。
- **inline `background-image` 覆盖 CSS 渐变**:banner 的 `background-image` 默认是 CSS 渐变(无图兜底);设了封面图时模板输出 inline `style="background-image:url(...)"`,inline 优先级高 → 覆盖渐变显示照片;`background-size/position` 留在 CSS 不被覆盖。两全。
- **悬挂头像的留白**:`.moments-cover__id` `bottom:-2.25rem` 让头像伸出 banner 下沿,`.moments-cover { margin-bottom:3rem }` 给悬挂部分腾位(移动端 2.5/2.25rem),不压到下方 feed。
- **封面图 TD 自行上传**(Customizer);未上传时显主题色渐变,不报错。昵称随左栏同源,无需另配。
- ⚠️ 部署:改了 PHP(模板 + Customizer + 版本)+ CSS,需把 `functions.php` + `archive-onedong_moment.php` + `moments.css` + `style.css` 一起 scp;刷腾讯云 CDN + 浏览器硬刷新;后台传封面图后 `refresh` 生效。


## v6.0.12(2026-07-03)· 作者详情页 author.php(封面 + 信息卡 + 文章/朋友圈)

### 背景
- TD 要求:点击作者昵称/头像 → 跳一个作者页。页面结构:① 顶部封面(背景图可上传 / 纯色),左下头像 + 昵称 + 签名(空 → 「无限进步」);② 封面下方左侧信息栏:地区(单独一栏)+ 性别 / 简介 / 站点;③ 右侧:作者发布的文章 + 朋友圈。
- 此前主题**无 `author.php`**(WP 回退 archive.php),作者头像 / 昵称均未链接到作者页;无签名 / 地区 / 性别 / 封面字段。

### 数据来源决策(关键)
- **用 WP 用户字段(user_meta + 后台「用户 → 个人资料」),不用 Customizer**:
  - 昵称 = `display_name`、简介 = `description`、站点 = `user_url`(均 WP 核心字段,本就在个人资料页)。
  - 新增 user_meta:`onedong_signature`(签名)/ `onedong_region`(地区)/ `onedong_gender`(性别)/ `onedong_cover`(封面图 URL)。
  - 理由:作者属性天然挂在 WP user 上;一处编辑(个人资料页)即可,且支持多作者扩展。Customizer 只放站点级配置。
- 字段编辑入口:后台「用户 → 个人资料」底部「OneDong 作者页」板块;封面图用 WP 媒体上传器(`wp.media`)+「选择 / 上传图片」按钮(仅 profile / user-edit 屏加载)。

### 改动
- **`functions.php`**:
  - 新增 `onedong_author_avatar_html($user_id,$size,$args)`:站点管理员(admin_email)走主题头像来源(logo/gravatar/custom,与左栏 / 朋友圈封面一致),其余作者走本人 gravatar。
  - 新增 user_meta 字段渲染(`show_user_profile` / `edit_user_profile`)+ 保存(`personal_options_update` / `edit_user_profile_update`,nonce + `edit_user` 权限)+ 媒体上传器(`wp_enqueue_media`)+ 页脚 JS(选择 / 清除 / 预览封面)。
  - `onedong_get_icon` 增 `info`(简介)、`gender`(性别)图标。
  - enqueue 扩展:`is_author()` 时加载 `author.css`;并把朋友圈包(`moments.css` + `moments.js` + qrcode + html2canvas)的加载条件加 `is_author()`(作者页朋友圈预览复用 `.moment` 卡 + lightbox + 点赞气泡)。
- **`author.php`(新建)**:`.author-page` 容器 → `.author-cover`(banner[inline bg-image 或主题色渐变兜底] + 左下 `.author-cover__id`[头像悬挂 + 昵称 h1 + 签名])→ `.author-body` 2 栏:
  - 左 `.author-info`(sticky):头像 + 昵称 + 地区 chip → 文章 / 朋友圈 统计 → 性别 / 简介 / 站点 / 加入于(均空则不渲染该行)。
  - 右 `.author-feed`:`.author-section` 文章(主查询 `have_posts()`,紧凑列表 `.author-posts`[缩略图 + 标题 + 日期 / 浏览 / 评论],`template-parts/pagination` 分页)+ 朋友圈预览(`WP_Query` 最新 6 条 + 「查看全部 →」/moments)。无朋友圈则隐藏该区块。
- **`assets/css/author.css`(新建)**:封面 hero + sticky 信息卡 + 紧凑文章列表(不重复作者头像)+ 响应式(≥900px 2 栏 / <900 单栏关 sticky / 手机缩封面)。全部用 token,浅暗自适应。
- **入口接线(→ `get_author_posts_url`)**:
  - `content.php`:文章卡头像(`.post-card__avatar-link`)+ 昵称(`.post-card__author-name a`)均链接作者页(layout.css 加链接样式)。
  - `sidebar-left.php`:左栏作者名链接作者页。
  - `archive-onedong_moment.php`:朋友圈封面昵称链接作者页。
- 版本 6.0.11→6.0.12-ProMax(`style.css` + `ONEDONG_VERSION`,刷 CSS/JS URL 缓存)。

### 坑 / 注意(关键)
- **作者归档默认开启**:WP 默认 `/author/<slug>/` 可用;若装了禁用作者归档的安全插件(防用户枚举)会 404,需放行。本主题未禁用。
- **头像一致性**:`onedong_author_avatar_html` 对 admin 复用主题头像来源(logo),保证作者页头像与左栏 / 朋友圈封面是同一张;非 admin 作者走本人 gravatar。
- **紧凑文章列表不复用 `content.php`**:`content.php` 卡片每张都带头像 + 昵称,在作者自己页面重复显示作者头像很冗余;故作者页用独立 `.author-posts` 紧凑行(缩略图 + 标题 + meta)。
- **封面纯色兜底**:无封面图时 banner 显示 `--primary→--primary-strong` 渐变(与朋友圈封面一致);设了封面图则 inline `background-image` 覆盖渐变。
- **封面图上传**:走用户个人资料页的 WP 媒体上传器(`wp.media`),非 Customizer;留空 = 纯色。`wp_enqueue_media` 仅在 profile / user-edit 屏加载。
- **enqueue 扩展是必须的**:作者页朋友圈预览复用 `.moment` 卡 + lightbox,必须把 moments 包加载条件加 `is_author()`,否则 `.moment` 无样式、图片 lightbox 不工作。
- ⚠️ 部署:改了 PHP(`functions.php` + `author.php` + `content.php` + `sidebar-left.php` + `archive-onedong_moment.php`)+ CSS(`layout.css` + `moments.css` + 新 `author.css`)+ `style.css`。scp 全部;后台「用户 → 个人资料」填写签名 / 地区 / 性别 / 封面后生效;刷腾讯云 CDN + 浏览器硬刷新。


## v6.0.13(2026-07-03)· 作者页右侧改「文章 / 朋友圈」标签(对齐参考稿)

### 背景
- TD 部署 v6.0.12 后给了一张参考稿(社交/博客风作者卡),要求:① 参考其布局;② 客户端**不显示「更换背景」「关注」**按钮(后台编辑即可);③ 统计把参考稿的「获赞 / 粉丝」换成「文章 / 朋友圈」。
- 参考稿关键结构:封面(右上「更换背景」)→ 头像(左下悬浮)+ 昵称 + 签名 + 「关注」→ 左资料栏(地区 / 性别 / 简介 / 站点)→ 右**标签**(文章 / 专栏 / 朋友圈)+ 文章列表 → 底部统计(获赞 / 粉丝)。
- 对照现状:封面 / 头像左下悬浮 / 昵称 + 签名 / 左资料栏 / 统计(已是 文章 / 朋友圈)均已对齐;「更换背景」「关注」本就未加;**唯一缺口 = 右侧改成标签**(原为「文章区 + 朋友圈预览」上下堆叠)。

### 改动
- **`author.php`**:右 `.author-feed` 由两个 `.author-section`(堆叠)改为**标签结构**:
  - `.author-tabs`(role=tablist)+ 两枚 `.author-tabs__btn`(文章[默认 is-active]/ 朋友圈),仅当有朋友圈时渲染(无朋友圈则不显示标签栏,直接展示文章)。
  - 两个 `.author-tab` 面板(文章 `is-active` 默认显示 / 朋友圈默认隐藏),文章复用主查询 + 分页,朋友圈复用 `.moments-feed` + 「查看全部朋友圈 →」。
  - 去掉原 `.author-section__title`(标签即标题),补 ARIA(tablist / tab / tabpanel + aria-selected / controls)。
- **`assets/css/author.css`**:新增 `.author-tabs`(下划线式标签栏)+ `.author-tab`(display:none / .is-active:block)+ `.author-tab__more`。
- **`assets/js/author.js`(新建)**:点标签 → toggle `.is-active`(按钮 + 面板)+ aria-selected。纯渐进增强(无 JS 时文章默认可见)。
- **`functions.php`**:`is_author()` 增加 enqueue `author.js`;版本 6.0.12→6.0.13。

### 坑 / 注意
- **统计位置未动**:参考稿统计在底部,TD 的「获赞→文章、粉丝→朋友圈」是改标签;本主题统计已在左信息卡且为 文章 / 朋友圈,沿用信息卡(不另设底部行,避免与标签重复)。若 TD 要挪到底部再说。
- **无 JS 友好**:`.author-tab` 默认 `display:none`,文章面板带 `is-active` 常显 → 关 JS 仍可见文章;朋友圈面板需 JS 才显(渐进增强可接受,且「查看全部」入口在 /moments)。
- **「专栏」标签未加**:参考稿有「专栏」tab,但本主题无专栏 CPT,故只 文章 / 朋友圈 两标签。
- ⚠️ 部署:`author.php` + `assets/css/author.css` + 新 `assets/js/author.js` + `functions.php` + `style.css`;刷腾讯云 CDN + 浏览器硬刷新。


## v6.0.14(2026-07-03)· 作者页右侧整体一张卡(tab 栏与内容连为一体)

### 背景
- TD:v6.0.13 后右侧「文章 / 朋友圈」标签 + 列表是**碎片化**的——tab 栏无背景浮在上方,下方 `.author-posts` / `.moments-feed` 各是独立卡。要求:**右侧整体做成单独一张卡**,且 tab 栏要与下方内容**连在一起**(卡头 + 卡身一体)。

### 改动(纯 CSS,`author.php` 未动)
- `.author-feed`(右侧容器)加卡背景:`--card-bg` + border + radius + shadow,与左栏信息卡对称成两张卡。
- **tab 栏作卡头**:`.author-tabs` `margin-bottom:0`(原 1.25rem)+ 贴顶,其 `border-bottom` 作 tab 与内容的分隔线 → tab 栏与下方列表在同一张卡内连为一体。
- **内层去卡背景(避免卡中卡)**:`.author-feed .author-posts` / `.moments-feed` 去 `background / border / border-radius`(原各自是独立卡),融入外卡;`.moments-feed` 同时 `max-width:none`。
- 分页 / 「查看全部」入卡后补内边距(`padding: ... 1.1rem`),与列表行左对齐。
- 版本 6.0.13→6.0.14。

### 坑 / 注意
- **无 overflow:hidden**:卡未设 `overflow:hidden`(故意),避免裁掉朋友圈 `moment__pop`(•• 菜单)/ 飞心动画等 `position:absolute` 元素;圆角靠内容内边距留白保证(内容不贴卡边,圆角处只显卡背景)。
- **左侧信息卡未动**:TD 只提右侧;左栏沿用独立卡。
- ⚠️ 部署:仅 `assets/css/author.css` + `functions.php` + `style.css`(刷 `?ver=6.0.14`);刷腾讯云 CDN + 浏览器硬刷新。


## v6.0.15(2026-07-03)· 作者页 tab 选中态改为底部一条线(::after 伪元素)

### 背景
- TD:右侧 文章 / 朋友圈 tab 选中态要是一条**底部的线**(下划线指示器)。

### 改动(`assets/css/author.css`)
- 旧:按钮 `border-bottom: 2px solid transparent` + 激活 `border-bottom-color: var(--primary)` + `margin-bottom:-1px`(靠 border 当指示线,与 tab 栏分隔线对齐)。
- 新:去掉按钮 border / margin 负值;选中态用 `::after` 伪元素画一条主色线(`position:absolute; left:0; right:0; bottom:-1px; height:2px; background:var(--primary)`),压在 tab 栏分隔线上 → 选中 tab 正下方一条清晰主色线,其余位置是灰色分隔线。更稳、更明显(不依赖 border 与分隔线的层叠)。
- 版本 6.0.14→6.0.15。

### 坑 / 注意
- `::after` 用 `bottom:-1px` 覆盖 tab 栏 `border-bottom`(分隔线)在选中 tab 下方那一段 → 视觉上选中处是 2px 主色线、其余 1px 灰线。
- 卡未设 overflow:hidden(同 v6.0.14),`::after` 的 -1px 不受裁剪。
- ⚠️ 部署:`assets/css/author.css` + `functions.php` + `style.css`;刷腾讯云 CDN + 浏览器硬刷新。


## v6.0.16(2026-07-03)· 作者页 tab 选中态只留底部蓝线(去掉文字色变化)

### 背景
- TD:点 tab 时去掉按钮的「选中状态」,只要底部一条蓝线。

### 改动(`assets/css/author.css`)
- 删 `.author-tabs__btn.is-active { color: var(--text); }`(原选中时文字变深 `--text`)。选中 tab 文字回到默认 `--text-faint`(与未选中一致),唯一区分 = `::after` 底部蓝线。
- 版本 6.0.15→6.0.16。

### 坑 / 注意
- 现在所有 tab 文字同为 `--text-faint`,仅靠蓝线区分选中;若觉得文字偏淡,可把 `.author-tabs__btn` 基色从 `--text-faint` 调到 `--text-muted`(更易读)。
- ⚠️ 部署:`assets/css/author.css` + `functions.php` + `style.css`;刷腾讯云 CDN + 浏览器硬刷新。


## v6.0.17(2026-07-03)· 左栏去头像昵称+统计下移 / 封面遮盖头像昵称 / tab 去底色

### 背景(TD 三条 + tab 底色一条)
1. 左栏去掉头像昵称(头像昵称只在封面)。
2. 左栏 文章 / 朋友圈 统计移到「加入于」下面(卡底)。
3. 封面背景要把左下角头像昵称「遮盖住」(头像昵称坐进 banner,banner 背景在身后)。
4. tab 选中时还有底色 → 只要底部蓝线。

### 改动
- **`author.php`**:删左栏 `.author-info__head`(头像 + 昵称 + 地区 chip);地区改为 dl 首行(map-pin);`.author-info__stats`(文章 / 朋友圈)从卡顶移到 dl 之后(卡底)。删未用的 `$info_avatar`。
- **`assets/css/author.css`**:
  - 封面:`.author-cover__id` `bottom:-2.75rem → 1.5rem`(头像昵称移入 banner 左下,banner 背景遮盖在身后);`.author-cover` `margin-bottom:3.5rem → 2rem`(不再给悬挂头像腾位);banner 高度 clamp(200,26vw,300)→clamp(220,28vw,300)。移动端同步(768:id bottom 1rem;480:0.75rem)。
  - 左栏:`.author-info__stats` 改 `margin:1.3rem 0 0` + 仅 `border-top`(卡底,去 border-bottom);`.author-info__list` `margin:0`(升为卡首元素)。
  - **tab 去底色(关键)**:全局 `button:hover{background:var(--primary-strong)}`(layout.css:1273)会在悬停 / 点击 tab 时透出蓝底。补 `.author-tabs__btn:hover/:focus/:active{background:none}`(特异性 0,2,0 > `button:hover` 0,1,1)压掉 → 选中 tab 无底色,只有 `::after` 蓝线。
- 版本 6.0.16→6.0.17。

### 坑 / 注意
- **底色根因是全局 button**:`.author-tabs__btn{background:none}` 只压了默认态;hover / focus / active 没压 → 全局 `button:hover` 蓝底透出。任何「裸 button 当 tab / 图标按钮」都要把 hover / focus / active 的 background 显式压掉(同 v6.0.6 `.entry-share` 的教训)。
- **头像昵称只在封面**:左栏不再有头像 / 昵称;封面 banner 左下角承载头像 + 昵称 + 签名,banner 背景在其身后(纯色渐变或上传图)。
- `.author-info__head / __avatar / __name / __region / __chip` CSS 残留未删(PHP 已不用),无害。
- ⚠️ 部署:`author.php` + `assets/css/author.css` + `functions.php` + `style.css`;刷腾讯云 CDN + 浏览器硬刷新。


## v6.0.18(2026-07-03)· 地区单独一栏 + 自定义字段(QQ / 微信 / 爱好 等)

### 背景
- TD:① 左侧地区「单独一栏」(独立成块,不混在列表);② 站点 / 加入于 等要能自定义扩展,自己加 QQ、微信、爱好 等。

### 改动
- **自定义字段(后台文本域)**:`functions.php` meta 键加 `onedong_extras`;后台「用户 → 个人资料」加「自定义字段」textarea(每行「标签: 值」,支持中文冒号「：」);保存逐行 `sanitize_text_field`。
- **`author.php`**:解析 extras(正则 `^([^:：]+)[：:](.*)$` 拆 标签 / 值);渲染为 dl 末尾的 `.author-info__row--custom` 行(加入于之后)。
- **地区单独一栏**:地区从 dl 行抽出,置顶为 `.author-info__region` 块(`<p>` + map-pin,主色加粗 + 下分隔线),独立于下方资料列表。
- **`author.css`**:删 v6.0.17 后残留的 head / avatar / name / chip 规则,改写 `.author-info__region`(置顶块);加 `.author-info__row--custom dt { padding-left:1.35rem }`(无图标行标签左留位,与带图标行对齐)。
- 版本 6.0.17→6.0.18。

### 坑 / 注意
- **自定义字段用文本域而非 repeater**:每行「标签: 值」最简、零 JS;支持中文冒号。值按纯文本 `esc_html` 输出(不做链接 / 富文本);要链接的话后续可加 URL 识别。
- **冒号解析**:正则用第一个冒号(半角 / 全角)分割;标签 / 值前后 trim;空行跳过。
- **自定义行无图标**:QQ / 微信 / 爱好 等无对应 icon,dt 只放标签;用 padding-left 与带图标行左对齐。
- ⚠️ 部署:`functions.php` + `author.php` + `assets/css/author.css` + `style.css`;后台「用户 → 个人资料」填「自定义字段」后生效;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.19(2026-07-04)· 后台个人资料头像上传 + 全站头像接管(自定义优先 / 国内 Cravatar 镜像)

### 背景
- 现状:全站头像 `get_avatar()` 默认走 Gravatar(`secure.gravatar.com`),国内网络访问不了 → 头像空白 / 裂图。主题原有「自定义头像」只对站点管理员一人(Customizer `onedong_avatar_source=custom`)生效,普通作者 / 评论者无自定义入口。
- TD 需求:后台「用户 → 个人资料」给每个用户加头像上传;并解决国内头像不展示。

### 改动(单文件 `functions.php`)
- **后台头像字段**:`onedong_author_meta_keys` 加 `avatar` → `onedong_avatar`;`onedong_author_profile_fields` 新增「头像」行(复用封面图的 WP 媒体库上传模式:`.onedong-media-upload` + `data-target/data-preview/data-title`);`onedong_author_save_profile_fields` 用 `esc_url_raw` 存。
- **上传 / 清除 JS 通用化**:`onedong_author_profile_footer_js` 重写 —— `buildPreview()` 按预览容器(圆头像 `.onedong-avatar-preview` / 封面 `.onedong-cover-preview`)渲染不同样式;封面「清除」按钮 class 由 `onedong-cover-clear` 统一为 `onedong-media-clear` + `data-preview`。头像 + 封面共用一套 JS。
- **全站头像接管(核心)**:
  - `pre_get_avatar_data` filter(`onedong_pre_get_avatar_data`):解析 user_id(ID / WP_User / WP_Post / WP_Comment / 带 user_id 的对象),取到 `onedong_avatar` 时直接给定 `url` + `found_avatar=true`,short-circuit 跳过 Gravatar。
  - `get_avatar_url` filter(`onedong_avatar_cravatar_mirror`):无自定义头像时,把 `gravatar.com`(含子域)正则替换为国内镜像 `cravatar.cn`(URL 结构完全兼容)。
  - 一处 filter 自动覆盖全站所有 `get_avatar()`:文章卡(content.php)、作者页(author.php / `onedong_author_avatar_html`)、评论(comments 回调)、朋友圈(inc/moments.php)、分享海报 —— 均无需逐个改。
- **作者头像 helper 前置自定义检查**:`onedong_author_avatar_html` 函数体最前查 `onedong_avatar`,有则直接 `<img>`(管理员也生效);否则维持原逻辑(管理员走主题来源 / 其他人走 get_avatar)。
- **preconnect**:`onedong_resource_hints` 头像预连接 `secure.gravatar.com` → `cravatar.cn`。
- 版本 6.0.18→6.0.19-ProMax(`style.css` + `ONEDONG_VERSION`)。

### 头像来源优先级
1. 用户「个人资料」上传的 `onedong_avatar`(最高,全站统一)
2. 站点管理员:主题 Customizer 头像来源(logo / custom / gravatar)
3. 其余:Gravatar(经 `cravatar.cn` 国内镜像)

### 坑 / 注意
- **`pre_get_avatar_data` vs `get_avatar_url` 分工**:前者负责「自定义头像 short-circuit」(设 `url` 后 WP 不再生成 Gravatar URL,故自定义头像不经镜像替换);后者只在未 short-circuit 时把 Gravatar 域名换成 cravatar。两者不冲突。
- **Cravatar 镜像**:`cravatar.cn` 与 Gravatar URL 结构一致(`/avatar/<md5>?d=retro&s=…`),直接换 host;无自定义头像的游客评论也能国内显示。Cravatar 自身会回源 Gravatar,首次可能略慢但可接受。
- **自定义头像存 URL 不存附件 ID**:与封面图一致(`att.url`);尺寸固定(get_avatar 的 `size` 不再生效),故建议源图 ≥160×160。若要按尺寸裁切,后续可改存附件 ID + `wp_get_attachment_image_src`。
- **管理员头像变更**:管理员在「个人资料」传头像后会覆盖 Customizer 的主题头像来源(全站作者卡 / 朋友圈封面统一走个人资料头像);未传则仍用 Customizer,行为不变。
- ⚠️ 本机无 PHP,语法未 `php -l` 复验,留待上线复验。部署仅需 `functions.php` + `style.css`;改了 PHP(非纯 CSS)bump `ONEDONG_VERSION` 刷 `?ver=`,页面 HTML 含头像 `<img>` 改动需刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.20(2026-07-04)· 深浅色切换按钮位置与右侧栏右边缘对齐

### 背景
- TD:切换按钮仍在 header 导航上,但水平位置要和下方右侧栏的右边缘对齐(原先按钮在 header 第三列左端,与右栏右边错位)。

### 改动(仅 `assets/css/layout.css` 一行)
- **`.header-controls` 加 `justify-content: flex-end`**:header 是三列 grid(16rem / 1fr / 16rem),controls 在第三列;原默认 `flex-start` 使按钮贴列左端,改 `flex-end` 后贴列右端。
- **为何这就对齐了**:`.site-header__inner` 与 `.site-content--three-col` 的 `max-width`(`var(--site-width)`)、`padding`(`0 clamp(1rem,4vw,2rem)`)、grid 列宽(16rem)、列间距(1.5rem)完全一致 → header 第三列右边缘 = 内容区第三列(右侧栏)右边缘。故 controls 贴列右 = 切换按钮与右栏右边垂直对齐。
- 版本 6.0.19→6.0.20-ProMax。

### 坑 / 注意
- **回退了同日早先一次错误尝试**:曾把按钮移进右栏(`sidebar.php` 加 toggle + 断点显隐 + JS `querySelectorAll`),与 TD 意图(留在 header)相反,已全部回退(`sidebar.php` / `theme-toggle.js` 恢复原样)。最终只改 `layout.css` 一行 `justify-content`。
- 桌面 / 移动端均适用 `flex-end`:移动端 ≤1180 header 变 flex,controls 由 `margin-left:auto` 推到最右,内部再 `flex-end` 无冲突。
- ⚠️ 部署仅需 `assets/css/layout.css`(纯 CSS;`ONEDONG_VERSION` 已在 6.0.20 bump,部署即刷 `?ver=`);刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.21(2026-07-04)· 修复深浅色切换图标偶发不显示(防御性立即设 data-pref)

### 背景
- v6.0.20 上线后 TD 反馈「看不到(切换)图标」。线上排查:CSS / JS / HTML / SVG 均已正确部署(6.0.20),`theme-toggle.js` 逻辑正确。
- 根因路径:toggle 按钮初始 `data-pref="auto"`,而 CSS 规则 `auto` 时 sun / moon 均 `display:none`;图标只在 `theme-toggle.js`(footer 加载)执行 init 把 `data-pref` 改成 light/dark 后才显示。若该 JS 延迟加载 / 失败 / 被浏览器缓存成旧版未执行 → `data-pref` 停在 auto → 图标空白。

### 改动(`header.php`)
- 在 `.theme-toggle` 按钮紧跟后加一段内联同步 `<script>`(随 HTML 加载,不依赖 footer 的 theme-toggle.js):复用 head anti-flash 同源逻辑,立即据 localStorage / 系统偏好算出 light/dark 并 `setAttribute('data-pref', p)` + `aria-pressed`。
- 这样无论 `theme-toggle.js` 加载状态如何,按钮渲染当下就有正确 `data-pref` → 图标立即可见;`theme-toggle.js` 加载后照常 init(设一致值 + 绑点击),无冲突。
- 版本 6.0.20→6.0.21-ProMax。

### 坑 / 注意
- **HTML 内联脚本对抗 JS 缓存**:HTML(PHP 动态输出)不被浏览器长期缓存,用户总拿到最新;而 `theme-toggle.js` 可能被缓存成旧版。内联脚本随 HTML 走,绕开 JS 缓存问题。
- **不替代 theme-toggle.js**:内联脚本只设初始状态(无点击绑定);点击切换仍由 `theme-toggle.js` 负责。两者设值同源,一致。
- **若仍不显示**:基本可断定为浏览器本地缓存(layout.css / theme-toggle.js 旧版)→ 硬刷新(Ctrl+Shift+R)/ 清缓存;或 CDN 边缘节点未刷新 → 刷腾讯云 CDN。
- ⚠️ 部署:`header.php` + `style.css`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.22(2026-07-06)· 资源导航模块(onedong_resource)

### 背景
- TD 提供需求文档(`Downloads/网站新增资源导航页面…docx`):新增独立「资源导航页」(`/resources/`)——全屏通栏 Banner + 分类筛选 + 资源卡片网格,全部后台可视化配置、零代码,响应式适配 PC/手机。
- 严格复刻既有 `inc/moments.php`(朋友圈)架构:CPT + meta box + nonce + 媒体上传 + Settings API。完整方案见 plan `C:\Users\Administrator.DESKTOP-VIVLMOS\.claude\plans\reflective-sniffing-pearl.md`。

### 改动(新增 6 文件 + 改 functions.php)
- **`inc/resources.php`(新增,模块主体)**:
  - CPT `onedong_resource`(slug `resources` → `/resources/`,菜单位 7)+ taxonomy `onedong_resource_cat`(hierarchical);CPT `show_in_menu='onedong-resources'` 挂自建顶级菜单。`add_image_size('onedong-resource-icon',96,96,true)`。一次性 flush(`onedong_res_flushed` option + `after_switch_theme`)。
  - 资源 meta box:网址(url 必填)/ 分类(单选 select)/ 排序权重 / 启停 / **图标三模式**(系统默认 | 本地上传 | 远程 URL);nonce + capability + sanitize 保存(照 moments)。分类走 `wp_set_object_terms`(taxonomy 关系,非 meta)。
  - 分类 term meta:排序权重 + 启停;`{tax}_add/edit_form_fields` + `created_/edited_{tax}` 保存;列表加「排序/状态」列;`pre_delete_term` 有资源禁删(`wp_die`)。
  - 顶级菜单「资源导航」+ 子页「页面设置」(Settings API,option `onedong_resources_settings`):导航名 / Banner 三模式 / 纯色 / 渐变(from/to/角度)/ 高度 / 主副标题。颜色用 `wp-color-picker`,Settings 挂 `admin_init`。
  - 前台 `pre_get_posts`:排除禁用资源 + 禁用分类下资源 + 按 `_onedong_resource_order` DESC 排序;`posts_per_page=-1`(前端 DOM 过滤需全量)。
  - nav filter(`wp_nav_menu_items` 判 primary + `wp_page_menu`)注入导航项,**不改 header.php**;`nav_label` 空则不注入(=关闭)。
- **`archive-onedong_resource.php`(新增)**:全宽布局,`onedong_resource_banner()`(三模式内联 style,纯颜色无图,高度走 `--res-h` CSS 变量便于移动端 @media 缩)+ `onedong_resource_filter_bar()`(启用+排序分类)+ 卡片 loop(`onedong_render_resource_card()`)。
- **`assets/css/resources.css` + `assets/js/resources.js`(新增)**:grid `auto-fill minmax(240px,1fr)` 响应式(768 单列 / 769–1100 两列);卡片走 tokens 变量自动深浅色;JS 纯 DOM 过滤(切分类 0 请求,无 jQuery)。
- **`assets/css/resource-admin.css` + `assets/js/resource-admin.js`(新增)**:图标三模式切换 + `wp.media`(限 image)选图 + 取色器 + Banner 模式切字段行 + 方向预设→角度。仅资源编辑页 + 设置页加载。
- **`functions.php`**:第 18 行下加 `require resources.php`;`onedong_scripts()` 加 `is_post_type_archive('onedong_resource')` 条件 enqueue。
- 版本 6.0.21→6.0.22-ProMax(`style.css` + `ONEDONG_VERSION`,刷 `?ver=` 缓存)。

### 关键决策
- **筛选用 JS 前端 DOM 过滤**(非 REST):首屏 PHP 全量渲染,切分类 `[hidden]` 切换,0 请求、瞬时、SEO 友好、与 moments「服务端渲染 + 渐进 JS」一致。未来上千条再升级 REST。
- **Banner 三模式纯颜色**:default 用 `var(--primary)`(品牌蓝统一)、solid 自定义 hex、gradient `linear-gradient(angle,from,to)`;绝不输出 `<img>`(需求明确不要图片背景)。
- **高度走 `--res-h` 变量**而非内联 `min-height`:内联 `min-height` 无法被 `@media` 覆盖,改 CSS 变量后移动端可 `calc(var(--res-h)*0.6)` 缩小。
- **介绍用经典编辑器**(post_content,与 moments 一致,享修订/自动保存);卡片摘要 `wp_trim_words(wp_strip_all_tags(get_the_content()),30)`。
- **名称=标题、介绍=正文、分类=taxonomy 关系**(非 meta),最贴 WP 惯例。

### 坑 / 注意
- **首次 `/resources/` 若 404**:`onedong_res_flushed` 已自动 flush;仍 404 则后台「设置→固定链接」点保存重刷。
- **`get_terms()` 不支持 orderby term meta** → 分类排序用「先查(排除禁用)再 `usort`」(按 `_onedong_rescat_order` + name 兜底),稳健无注入面。
- **禁用分类的资源也要隐藏**:`pre_get_posts` 同时 `meta_query`(资源自禁)+ `tax_query NOT IN`(分类禁),两条都要。
- **WP 媒体库非真压缩**:用 `onedong-resource-icon`(96×96)小尺寸省流 + `wp_attachment_is_image` 格式兜底;`wp.media({library:{type:'image'}})` 前端限图。
- **Settings API 必须挂 `admin_init`**(非 `admin_menu`),否则字段不生效;`show_in_menu` slug(`onedong-resources`)必须等于 `add_menu_page` 第 4 参,否则 CPT 脱离顶级菜单。
- **nav filter 必须判 `theme_location==='primary'`**,否则 footer 菜单也被注入;`is_admin()` 不注入。
- **`mb_strtolower` 改 `strtolower`**:避免 mbstring 扩展缺失时 fatal(中文资源名对 strtolower 安全)。
- ⚠️ 本机无 PHP,语法已人工核对(对照 moments 范本);待本地 WP 启用跑 `php -l` + 后台逐功能实测(见 plan 端到端验证 11 项)。
- ⚠️ 线上仍跑 Once-main 主题;资源页需部署 onedong + 启用 + 刷腾讯云 CDN 才生效。`onedong.zip` 沿用历史策略不纳入提交。

## v6.0.23(2026-07-06)· 资源导航 v1.1:Banner 间距可设 + 图片背景模式 + 卡片 hover 去下划线

### 背景
- TD 线上(dingxudong.com/resources)反馈三点:① Banner 紧贴顶部导航,要可后端设间距;② Banner 现纯色,要能上传图片(自适应铺满);③ 资源卡片 hover 有下划线,去掉。
- 排查:线上已部署 v6.0.22,导航注入正常(菜单末尾有「资源导航」项),Banner 默认纯色 200px。卡片下划线根因:`.resource-card__link{text-decoration:none}` 特异性 0,1,0,被全局 `a:hover`(base.css,0,1,1)压过 → hover 仍下划线。

### 改动(`inc/resources.php` + `assets/css/resources.css` + `assets/js/resource-admin.js` + `assets/css/resource-admin.css`)
- **Banner 顶部间距可设**:新设置项 `banner_top_gap`(0–200px,默认 0);`banner_style()` 输出 `--res-gap` 变量;`.resource-banner` 加 `margin-top:var(--res-gap,0px)`(上方露出 page-bg 形成留白)。
- **Banner 图片背景模式**(第 4 种):`banner_mode` 加 `image`;新设置项 `banner_image`(attachment ID);后台「页面设置」加「背景图片」字段(`wp.media` 选图 + 预览 + 移除);`banner_style()` image 分支输出 `url("...") center/cover no-repeat`(自适应铺满);`resource-admin.js` 的 `syncBannerMode` 加 image 字段行显隐。
- **卡片 hover 去下划线**:`.resource-card a:hover, .resource-card a:focus { text-decoration:none }`(特异性 0,2,1 > 全局 a:hover 的 0,1,1,胜出)。
- 版本 6.0.22→6.0.23-ProMax。

### 坑 / 注意
- **图片背景 cover**:用 `background:center/cover no-repeat`,图片填满 Banner 区域(min-height 仍由 `--res-h` 控制);小图会被放大,建议上传宽图(如 1600×400)。深色模式下图片不变暗(如需加深色遮罩,后续可加 `::before` 半透明层)。
- **间距是 margin-top**:留白露出 `--page-bg`(浅色灰 / 深色黑),非透明;0 = 紧贴(原状)。
- **卡片去下划线靠特异性**:`.resource-card a:hover`(0,2,1)压过 base.css `a:hover`(0,1,1);若将来 base.css 加 `!important` 需相应升级。
- ⚠️ 本机无 PHP,语法已人工核对;待部署后实测(后台选 image 模式传图、设间距、前台看卡片 hover 无下划线)。
- 部署:`inc/resources.php` + 3 个 assets + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.24(2026-07-06)· 资源导航 v1.2:卡片风格统一 + 「访问」CTA 引导(ui-ux-pro-max)

### 背景
- TD 用 ui-ux-pro-max skill 审查 dingxudong.com/resources,要求:① 页面元素与整站风格统一(圆角/卡片质感);② 资源卡片加引导点击/跳转的标识或文字。
- skill UX 指引:可点击元素须有 hover 反馈 + active 按压态 + cursor pointer,且不能只靠 hover(tap 也要反馈)。
- 排查发现 `--shadow-hover` 全站只被 layout.css:280 使用、**无任何定义** → `.post-card:hover` 实际只有 translateY(-2px)、阴影为空。

### 改动(`inc/resources.php` + `assets/css/resources.css`)
- **卡片底部加「访问 →」CTA**(`onedong_render_resource_card`):新 `.resource-card__foot`(左分类标签 + 右「访问」+ 箭头 SVG);hover 时箭头 translateX(2px) 右移 + CTA/标题/边框变 `--primary`,明确引导跳转。
- **风格对齐 `.post-card`**:卡片 `cursor:pointer`;hover 改 `translateY(-2px)`(对齐 post-card);padding `1rem→1.25rem 1.4rem`(靠拢 post-card 1.5rem);标题 hover 变 `--primary`(对齐 `.post-card__title a:hover`);图标 40→44px。
- **按压反馈(skill active state)**:`.resource-card:active{scale(0.99)}`、`.resource-filter:active{scale(0.96)}`(tap 也有反馈,非仅 hover)。
- 版本 6.0.23→6.0.24-ProMax。

### 坑 / 注意
- **未补 `--shadow-hover` 全局定义**:layout.css:280 用了但未定义(post-card hover 阴影一直为空)。资源卡 hover 用局部写死阴影 `0 8px 22px rgba(27,26,49,.1)`(不污染全局);若日后要全站统一 hover 阴影,在 tokens.css 补 `--shadow-hover` 浅/暗两值即可同时惠及 post-card。
- **CTA「访问」是纯展示文字 + 箭头**:整卡是 `<a target=_blank>`,CTA 不另开链接(避免 `<a>` 嵌套);hover 动画纯视觉引导。
- **hover 边框变 primary**:比 post-card(仅 translateY)反馈更强,因资源卡是外链 CTA,需更强点击引导(skill 高严重度:hoverable cards need feedback)。
- ⚠️ 本机无 PHP,语法已人工核对;待部署后实测(卡片 hover 标题/箭头/边框、移动端 tap 反馈、深浅色)。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.25(2026-07-06)· 资源导航 v1.3:卡片圆角后台可设 + Banner 渐变流动 / 图片呼吸动效

### 背景
- TD 用 ui-ux-pro-max skill:① 资源卡片圆角后台可设(默认跟随网站 --radius-large);② Banner 渐变 / 图片模式要有流动 / 呼吸动态展示。
- skill UX(animation 域):respect prefers-reduced-motion(高)、每屏最多 1-2 动画(高,Banner 单 hero 合规)、用 ease 非 linear(低)。

### 改动(`inc/resources.php` + `archive-onedong_resource.php` + `assets/css/resources.css`)
- **卡片圆角后台可设**:新设置项 `card_radius`(select:跟随网站 / 直角 / 6 / 12 / 20 / 药丸);新 section「卡片样式」;模板 `.resources-page` 输出 `--res-card-radius` 变量;`.resource-card{border-radius:var(--res-card-radius, var(--radius-large))}`(默认跟随网站)。
- **Banner 渐变流动**:`[data-mode="gradient"][data-animate]` + `background-size:200%` + `resBannerFlow` 16s ease infinite(background-position 0→100→0)。
- **Banner 图片呼吸(Ken Burns)**:image 模式改用 `--res-bg` 变量 + `::before` 伪元素 cover 显示图(替代原 section background-image,因 background-image 不能 transform);`[data-animate]::before` + `resBannerBreathe` 18s ease-in-out alternate(scale 1→1.08)。
- **背景动效开关**:新设置项 `banner_animate`(checkbox,默认开);仅 gradient / image 模式生效;banner section 输出 `data-mode` + `data-animate` 属性。
- **reduced-motion 兜底**:`@media (prefers-reduced-motion: reduce)` 禁用两个动画(skill 高优先级 a11y)。
- 通用 settings 回调扩展 select / checkbox 类型。
- 版本 6.0.24→6.0.25-ProMax。

### 坑 / 注意
- **图片改 ::before 才能呼吸**:`background-image` 无法 transform,scale 动画需伪元素;故 image 模式 section 不再直接 `background-image`,改 `--res-bg` 变量 + `::before`(z-index:0)+ 内容 z-index:1。无 `data-animate` 时 `::before` 仍静态显示图(呼吸关闭)。
- **渐变流动靠 background-size:200%**:linear-gradient 默认 100% 无移动空间,放大到 200% + position 动画才有流动感。
- **每屏单动画合规**:仅 Banner 一个循环动画(筛选 / 卡片 hover 是交互反馈非循环动画),符合 skill「animate 1-2 key elements」。
- **卡片圆角默认空字符串**:select 选「跟随网站」= 不输出变量,CSS `var(--res-card-radius, var(--radius-large))` 兜底到网站圆角。
- ⚠️ 本机无 PHP,语法已人工核对;待部署后实测(后台选圆角、gradient 流动、image 呼吸、系统开「减少动态效果」验证兜底)。
- 部署:`inc/resources.php` + `archive-onedong_resource.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.26(2026-07-06)· 资源导航 v1.4:Banner 内容卡片(玻璃磨砂)后台可设

### 背景
- TD 用 ui-ux-pro-max skill:Banner 背景模式(纯色 / 图片 / 渐变)下新增「卡片样式」后台设置 —— 把标题 / 副标题做成可配置卡片,增强彩色 / 图片背景上的层次与可读性。
- skill Glassmorphism 指引:`backdrop-filter blur(10-20px)` + 半透明底 + 浅 border + 文字色翻转保证 4.5:1;common rules 提醒浅模式用高透明度(bg/80+ 非 /10)。

### 改动(`inc/resources.php` + `assets/css/resources.css`)
- **Banner 内容卡片(玻璃磨砂)**:新设置项 `banner_card`(开关,默认关)+ `banner_card_radius`(select:跟随网站 / 直角 / 8 / 16 / 24 / 药丸)+ `banner_card_padding`(select:紧凑 / 正常 / 宽松);均在「页面设置 → 顶部 Banner」section。
- 渲染:`.resource-banner__inner` 启用时加 `--card` class + 内联 `border-radius`/`padding`;新增 `onedong_resource_banner_card_style()` 生成内联 style。
- CSS `.resource-banner__inner--card`:`backdrop-filter:blur(12px)` + 浅模式 `rgba(255,255,255,.86)`(高透明度保对比)+ border + 阴影 + max-width 720px 居中;**文字色翻转**为 `var(--text)`(浅深 / 暗浅)+ 去 text-shadow;暗模式 `rgba(20,20,22,.72)`。
- 版本 6.0.25→6.0.26-ProMax。

### 坑 / 注意
- **文字色翻转是关键**:卡片启用前标题 / 副标题是白色(彩色背景上);启用后卡片是浅底,白字不可见 → 翻转为 `var(--text)`(浅模式 #1D2129 深、暗模式 #F7F8FA 浅),并去掉 text-shadow(卡片上不需要)。
- **玻璃对比度**:浅模式用 0.86 高透明度(common rules:勿用 0.1-0.3 太透)+ blur 12px;暗模式 0.72 + 浅文字,保 4.5:1。
- **默认关**:不改变现有 Banner 外观(无卡片),后台勾选「内容卡片」启用。
- **backdrop-filter 兼容**:加 `-webkit-` 前缀(Safari);不支持 blur 的浏览器仍显示半透明底(降级可读)。
- ⚠️ 本机无 PHP,语法已人工核对;待部署后实测(纯色 / 图片背景 + 开卡片、圆角 / 内边距、深浅色文字对比)。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.27(2026-07-06)· 资源导航 v1.4.1:移除「内容卡片内边距」设置

### 背景
- TD 反馈:「内容卡片」功能保留,但「内容卡片内边距」设置项不需要 —— 内边距改固定值,后台不再可调。

### 改动(`inc/resources.php` + `assets/css/resources.css`)
- 移除 `banner_card_padding`:defaults / settings_init / sanitize / `onedong_resource_banner_card_style()` 四处删除;padding 从内联改到 CSS 固定 `1.5rem 2.5rem`(`.resource-banner__inner--card`)。
- 保留:`banner_card`(开关)+ `banner_card_radius`(圆角)。
- 版本 6.0.26→6.0.27-ProMax。

### 坑 / 注意
- 内边距固定 `1.5rem 2.5rem`(原「正常」档值),如需调整改 `resources.css` 的 `.resource-banner__inner--card { padding }`。
- ⚠️ 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.28(2026-07-06)· 资源导航 v1.4.2:Banner 圆角后台可设

### 背景
- TD:给 Banner(顶部通栏 section)加圆角设置,功能同「内容卡片圆角」。

### 改动(`inc/resources.php` + `assets/css/resources.css`)
- 新设置项 `banner_radius`(select:直角默认 / 8 / 16 / 24 / 药丸),在「页面设置 → 顶部 Banner」section。
- `banner_style()` 输出 `--res-banner-radius` 变量(主 return + image case return 两处);CSS `.resource-banner{border-radius:var(--res-banner-radius,0px);overflow:hidden}`。
- `overflow:hidden` 双重作用:① 圆角裁背景图 / 渐变;② 防 image Ken Burns scale 放大溢出(此前 banner 无 overflow,image ::before scale 1.08 会溢出到 banner 外)。
- 版本 6.0.27→6.0.28-ProMax。

### 坑 / 注意
- **默认直角**:`banner_radius=''` → 0px,保持现有全屏通栏方角外观(不改变现状),后台选圆角才生效。
- **圆角 + margin-top gap**:Banner 有顶部间距 + 圆角后,四角露出 page-bg,视觉为「漂浮圆角通栏」。
- **overflow:hidden 影响定位**:banner 内 __inner / 内容均为静态 / relative,不受裁剪影响;只裁 ::before 背景。
- ⚠️ 本机无 PHP,语法已人工核对;待部署后实测(选圆角 + 纯色 / 图片背景,看四角圆 + 图片裁圆角)。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.29(2026-07-06)· 资源导航 v1.4.3:Banner 圆角新增「跟随网站」选项

### 改动(`inc/resources.php`)
- `banner_radius` select 新增 `'site'` =>「跟随网站」选项(应用 `var(--radius-large)`),与内容卡片圆角 / 卡片圆角的「跟随网站」一致。
- sanitize 白名单加 `'site'`;`banner_style()` 的 `$brad` 加 `'site'` 分支 → `var(--radius-large)`。
- 保留原选项:直角(默认)/ 8 / 16 / 24 / 药丸。
- 版本 6.0.28→6.0.29-ProMax。

### 坑 / 注意
- 「跟随网站」用 `var(--radius-large)`(tokens.css 12px),自动跟随主题圆角令牌。
- 默认仍为直角(''),不改变现状。
- 部署:`inc/resources.php` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.30(2026-07-06)· 资源导航 v1.4.4:纯色背景透明度可设

### 背景
- TD:Banner「系统默认(品牌蓝)」「自定义纯色」两种纯色背景新增透明度设置。

### 改动(`inc/resources.php`)
- 新设置项 `banner_opacity`(number 0-100,默认 100 不透明),在「页面设置 → 顶部 Banner」section。
- `banner_style()`:加 `$op`;switch 后对 `default`/`solid` 模式用 `color-mix(in srgb, {bg} {op}%, transparent)` 应用透明度(`$op<100` 才生效);`gradient`/`image` 不受影响。
- 版本 6.0.29→6.0.30-ProMax。

### 坑 / 注意
- **color-mix 兼容**:tokens.css 已用 color-mix(站点支持);旧浏览器不识别则背景回退为不透明(降级,无害)。
- **仅纯色模式生效**:gradient(双色渐变)/ image(图片)不加透明度;后台字段常显但 desc 注明仅纯色。
- **透明度 + 白字对比**:纯色半透明后露出 page-bg,白字对比可能下降;建议配合「内容卡片」(文字进卡片)或保持较高透明度。
- **默认 100**:不改变现有不透明外观。
- ⚠️ 本机无 PHP,语法已人工核对;待部署后实测(default/solid 调透明度,gradient/image 不变)。
- 部署:`inc/resources.php` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.31(2026-07-06)· 资源导航 v1.4.5:Banner 限宽对齐卡片网格

### 背景
- TD:Banner 全宽通栏(100%)与下方资源卡片网格(max-width 1280 居中)左右不对齐;要 Banner 和卡片同宽对齐。
- skill common rule:Consistent max-width(同 max-width,勿混容器宽度)。

### 改动(`assets/css/resources.css`)
- `.resource-banner`:`width:100%` → `max-width:var(--site-width,1280px)` + `margin:var(--res-gap,0px) auto 0`(限宽居中,与 `.resources-main` 同 max-width 同居中 → 左右对齐)。
- Banner 不再全屏通栏,变成与内容区等宽的居中块;圆角 / overflow / gap 等其他属性不变。
- 版本 6.0.30→6.0.31-ProMax。

### 坑 / 注意
- **Banner 块 vs grid 边**:Banner 背景 = max-width 1280 边;grid 在 `.resources-main` padding(1.25rem)内 → Banner 背景比 grid 左右各宽 1.25rem(块对齐)。若要 Banner 严格 = grid 边,改 Banner max-width 为 `calc(var(--site-width) - 2.5rem)`(本期按「块对齐」,与内容区等宽)。
- **移动端**:≤768 Banner padding 1rem = resources-main 左右 1rem,对齐。
- ⚠️ 待部署看 Banner 与卡片左右对齐效果。

## v6.0.32(2026-07-07)· 全站 Sticky Footer:短页面页脚贴视口底部

### 背景
- TD:资源页 `/resources` 内容少(一个 Banner + 一个卡片 + 空提示)撑不满一屏,Copyright / ICP 底部信息栏(`.site-info`)悬在半空,下方留大片空白;要求「底部信息栏到最底部」。

### 改动(`assets/css/layout.css`)
- `body`:加 `min-height:100vh`( + `100dvh` 移动端动态视口 fallback)+ `display:flex; flex-direction:column`,整页变弹性纵列。
- `.site-main`:加 `flex:1 0 auto`,占满 header 与 footer 之间的剩余高度。
- `.site-footer`:加 `flex-shrink:0`,确保页脚不被压缩、始终贴底。
- 版本 6.0.31→6.0.32-ProMax(`style.css` Version + `functions.php` `ONEDONG_VERSION` 同步)。

### 坑 / 注意
- **全局生效,不止资源页**:所有短页面(空归档 / 404 / 搜索无结果)页脚都贴底,符合普遍期望;长页面(文章流 / 文章详情)内容溢出后 `#main` 自然撑高,页脚跟在后面,**不受影响**。
- **flex 不破坏 sticky header**:`.site-header` 是 flex item + `position:sticky`,sticky 在 flex 容器内仍正常工作。
- **body 直接子项**:`.skip-link`(absolute)、`.nav-overlay`(PC `display:none` / 移动端 fixed)均脱离流,不占 flex 空间;`.footer-widgets` 条件渲染,作为普通 flex item 紧跟 `#main` 之后。
- **`margin-top:3rem` 保留**:页脚上方留白照旧,参与 flex 布局计算。
- 本机无 PHP,纯 CSS 改动无需运行时验证;待部署看短页面页脚是否贴底。
- 部署:`assets/css/layout.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.33(2026-07-07)· 资源页对齐 header + 修筛选栏分类缺失

### 背景
- TD:资源页 Banner / 筛选栏 / 卡片网格左右要与顶部 header(logo 最左、深浅色切换按钮最右)对齐;且筛选栏目前只有「全部」,其他分类不显示。

### 改动一:对齐(`assets/css/resources.css`)
- `.resource-banner` / `.resources-main`:由 `max-width:site-width + margin:auto` 改为 `width:calc(100% - clamp(2rem,8vw,4rem)) + max-width:calc(site-width - clamp(2rem,8vw,4rem)) + margin:auto`。`clamp(2rem,8vw,4rem)` = 2 × header `.site-header__inner` 的 `padding:clamp(1rem,4vw,2rem)`,使色块 / 内容区左右边 = header 内容边(logo 左 / toggle 右),大中小屏全对齐。
- `.resources-main` 水平 padding `1.25rem → 0`:筛选栏 / 卡片网格贴 header 内容边,不再二次内收。
- `@768`:`.site-header__inner` 小屏 padding 固定 1rem(layout.css)→ Banner / main 覆盖 `width:calc(100% - 2rem); max-width:none`,水平 padding 归零。

### 改动二:筛选栏分类缺失(`inc/resources.php`)
- `onedong_resource_get_cats()` 的 `meta_query` 由「严格匹配 `_onedong_rescat_enabled='1'`」改为「排除显式禁用」(`'!=' '0'` OR `NOT EXISTS`)。
- 根因:后台全用「不是 '0' 即启用」语义(资源勾选 L153 / 分类勾选 L288 / 状态列 L330 / 新建默认 checked L269),但前台取数却要求严格 `='1'`,漏掉未设 meta 的老 / 导入分类 → 筛选栏只剩硬编码「全部」。改后语义统一,显式禁用的分类仍不显示。

### 坑 / 注意
- **对齐用 width + max-width 双约束**:大屏 max-width 生效(= site-width - 2pad 居中),中小屏 width 生效(= vw - 2pad)。单一 max-width 在小屏失效(满屏不对齐),单一 width 在大屏会超 site-width。
- **Banner 内部 padding 保留**:色块边缘由 width 决定,内部 padding(标题内收)不影响外对齐。
- **clamp 倍数关系**:`clamp(2rem,8vw,4rem)` 必须严格 = 2 × `clamp(1rem,4vw,2rem)`,否则与 header 错位;日后改 header padding 时记得同步本处。
- **分类修复是数据兼容**:老分类(无 meta)现在自动显示;若 TD 想隐藏某分类,仍可在后台取消「启用此分类」(写 `enabled='0'`)。
- ⚠️ 本机无 PHP,语法已人工核对;待部署实测:① 筛选栏出现其他分类 ② Banner / 卡片左右与 logo / toggle 对齐 ③ 小屏对齐。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `assets/css/layout.css`(6.0.32 sticky footer)+ `style.css` + `functions.php`;bump `ONEDONG_VERSION` 6.0.32→6.0.33 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.34(2026-07-07)· 资源卡 hover:展开介绍 + 主题色渐变环绕光边

### 背景
- TD:资源卡 hover 时 ① 把未显示完整的介绍展开 ② 卡片边框变主题色渐变环绕旋转动画。
- ui-ux-pro-max skill 指导(ux 域,均 High):① 必须尊重 prefers-reduced-motion ② hover 在触屏失效,重要交互不要只靠 hover ③ 避免过多动画;Common Rule:hover 不用 scale 导致 layout shift。

### 改动(`assets/css/resources.css`)
- **展开介绍**:`.resource-card__desc` 加 `max-height:3.2em`(≈2 行)+ transition;hover 时 `line-clamp:unset` + `max-height:40em` + 文字加深。用 `max-height` 过渡实现平滑展开(line-clamp 本身不能 transition)。
- **不撑高同行**:`.resource-grid` 加 `align-items:start`,卡片各自自然高度,hover 展开只动本卡。
- **渐变环绕光边**:`.resource-card::before` 用 `conic-gradient(from var(--res-angle))` 双光弧 + `@property --res-angle` 注册 + `@keyframes resCardBorderSpin` 旋转;`mask`(content-box + exclude)镂空仅留 1.5px 边;hover `opacity 0→1` + 旋转 3s。
- **层叠**:`.resource-card` 加 `position:relative; isolation:isolate`;`.resource-card__link` 加 `position:relative; z-index:1`(内容浮于光边之上)。

### 坑 / 注意
- **触屏降级**:整个 hover 效果包进 `@media (hover:hover)`,触屏(:hover 会粘住)降级为静态 2 行卡。原 `.resource-card:hover`(transform/shadow/border-color,无 @media)保留,触屏 tap 仍 sticky 上浮(历史行为,未动)。
- **@property 降级**:不支持 `@property` 的旧浏览器(Chrome<85 / Safari<16.4 / FF<128)angle 不转 = 静态渐变边,仍可见无动画 —— 可接受。
- **reduced-motion**:`.resource-card:hover::before { animation:none }`(光边静止仍显示)+ `__desc { transition:none }`。
- **conic 双弧**:`transparent 0%→primary 12%→transparent 30%` / `70%→primary 88%→100%`,两道对称光弧绕圈;想改单弧调一处即可。
- **max-height 40em**:em 相对 desc font-size(.88rem),≈563px,远超 `wp_trim_words(30)` 介绍,确保展开不裁剪。
- **光边盖原 border**:hover 时光边(1.5px,opacity 1)盖原 border(1px line)上,视觉为旋转渐变;未 hover 显示原 line 边。
- ⚠️ 本机无 PHP / 浏览器,纯 CSS 待 TD 部署后实测:① hover 描述展开 ② 光边旋转 ③ 浅 / 深主题对比 ④ 触屏不触发 ⑤ reduced-motion 静止。
- **部署分工变更**(2026-07-07):TD 自管打包 / 部署 / CDN,Claude 只 push GitHub;本次不打包,仅 commit + push。bump `ONEDONG_VERSION` 6.0.33→6.0.34。

## v6.0.35(2026-07-07)· Banner 满宽重构 + 底部精选 4 卡

### 背景
- TD:Banner 宽度对齐 header 内容外框(满 1280 —— v6.0.33 内收后嫌太窄);Banner 底部新增 4 个资源卡片(按权重前 4);网格保留全部。
- AskUserQuestion 定向:宽度=满内容区 1280 / 4 卡=按权重前 4 / 网格=照常全部。
- ui-ux-pro-max:资源页 = Directory 模式(Hero + Categories + Featured Listings),4 卡 = Featured Listings;ux 预留尺寸防 layout shift、多断点测试。

### 改动
- **撤销 v6.0.33 内收**:`.resource-banner` / `.resources-main` 由 `width:calc(100% - clamp(...))` 改回 `max-width:var(--site-width); margin:auto`(满内容区,与 header 内容外框等宽);main padding 恢复 `1.25rem`(与 Banner padding 一致 → 网格与 4 卡左右对齐)。@768 同步撤销。
- **Banner 结构**:`.resource-banner` display 改 `flex-direction:column` + gap,容纳「标题区 + 4 卡区」;`inc/resources.php` `onedong_resource_banner()` 末尾调 `onedong_resource_banner_cards()`。
- **新函数 `onedong_resource_banner_cards()`**:`WP_Query` 查 4 个启用资源(按 `_onedong_resource_order` DESC + title ASC,排除 `_onedong_rescat_enabled=0` 分类),与主查询 `pre_get_posts` 同序;loop 内 `the_post()` + 复用 `onedong_render_resource_card()`(自带 hover 光边 / 上浮);`wp_reset_postdata()` 收尾。无资源则不输出。
- **4 卡样式**:`.resource-banner__cards` grid `repeat(4, minmax(0, 1fr))`;Banner 内 `.resource-card` `text-align:left`(覆盖 Banner center);desc `line-clamp:1`(紧凑)+ hover 不展开(避免撑高 Banner 抖动)。
- **响应式**:1100–769 4 卡 → 2×2;≤768 4 卡 → 2×2。

### 坑 / 注意
- **撤销 v6.0.33**:本期把 v6.0.33 的「内收到 logo/toggle content 边」改回「满内容区 1280」。两版方向相反 —— v6.0.33 贴 header 内容边(窄),v6.0.35 满 header 外框(宽)。日后若再调,先确认 TD 要哪个方向。
- **4 卡 = 主查询前 4**:Banner `WP_Query` 参数与 `pre_get_posts` 完全一致(同排序、同 `enabled='1'`、同排除禁用分类),保证 Banner 4 卡 = 网格前 4 张。改排序逻辑要两处同步。
- **Banner 内卡不展开**:网格卡 hover 展开介绍(v6.0.34),但 Banner 内卡 hover 不展开(否则撑高 Banner 整体抖动),只保留光边 / 上浮。`.resource-banner__cards .resource-card:hover .resource-card__desc` 覆盖回 `clamp:1`。
- **图片模式层叠**:`.resource-banner[data-mode="image"] > * { z-index:1 }`,`__cards` 是直接子,在图片 `::before` 之上,白卡叠图可读。✓
- **Banner 高度**:`min-height` 280 仍是下限,标题 + 4 卡内容撑高到约 450px,正常。
- ⚠️ 本机无 PHP,语法已人工核对(`WP_Query` + `the_post()` + `wp_reset_postdata()` 标准用法);待 TD 部署实测:① Banner 满 1280 宽 ② 底部 4 卡横排(权重前 4)③ 网格仍全部 ④ 平板 / 移动 2×2 ⑤ 浅 / 深主题 + 各 banner 背景模式(纯色 / 渐变 / 图片)下 4 卡可读。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.34→6.0.35。Claude 只 push GitHub,部署 TD 自管。

## v6.0.36(2026-07-07)· 移除 Banner 底部精选 4 卡

### 背景
- TD:去掉 Banner 里面的资源卡片展示(v6.0.35 加的 4 卡)。

### 改动
- `inc/resources.php`:`onedong_resource_banner()` 移除 `onedong_resource_banner_cards()` 调用;删除 `onedong_resource_banner_cards()` 函数整体(死代码清理)。
- `assets/css/resources.css`:`.resource-banner` display 由 `flex-direction:column`(为容纳 4 卡)恢复 `align-items:center; justify-content:center`(单标题居中),padding `2.5rem→2rem`;删除 `.resource-banner__cards` 样式块 + 1100 / 768 响应式中的 cards 规则。
- **保留**:Banner / main 满内容区 1280(v6.0.35 撤销内收的成果不变);网格卡 hover 展开 + 渐变光边(v6.0.34)。
- 版本 6.0.35→6.0.36-ProMax。

### 坑 / 注意
- Banner 回到纯标题英雄区(满宽 1280 居中),无卡片。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.35→6.0.36。Claude 只 push GitHub,部署 TD 自管。

## v6.0.37(2026-07-07)· Banner 满屏全宽 + 分类按钮 hover 白字

### 背景
- TD:满宽 1280 大屏两侧留白仍嫌窄 → Banner 改**满屏全宽(100vw)**;筛选分类按钮 hover 文字变白。
- 线上诊断(本机直连核对):服务器 `resources.css` / `style.css` 已是 6.0.36 满宽 1280,但 `functions.php` 漏传(戳仍 6.0.34)→ 浏览器缓存旧窄版。

### 改动(`assets/css/resources.css`)
- **Banner 满屏**:`.resource-banner` 由 `max-width:site-width + margin:auto` 改 `width:100vw + max-width:100vw + margin-left/right:calc(50% - 50vw)`(突破 #main 限宽到视口左右缘)。标题仍由 `.resource-banner__inner`(max-width site-width)限宽居中。
- **防横向滚动**:加 `body { overflow-x: clip }`(100vw 含滚动条宽度会溢出;clip 裁掉超出 viewport 部分,且 clip 不创建 scroll container,不影响 `.site-header` 的 sticky)。
- **分类 hover 白字**:`.resource-filter:hover` 由 `color:var(--primary)` 改 `background:var(--primary) + border-color:var(--primary) + color:#fff`(primary 底白字,与 `.is-active` 视觉一致)。
- 版本 6.0.36→6.0.37-ProMax。

### 坑 / 注意
- **满屏 margin trick**:`calc(50% - 50vw)` 中 `50%` 相对 Banner 包含块(`.resources-page` = #main content 宽)。#main 必须 margin auto 居中,公式才成立(v6.0.32 sticky footer 给了 #main flex + margin auto,满足)。
- **overflow-x clip vs hidden**:用 clip 不用 hidden —— hidden 创建 scroll container 会破坏 `.site-header` position:sticky(顶栏不再吸顶);clip 不影响 sticky。clip 需较新浏览器(Chrome 90+ / Safari 16+ / FF 81+,2020 年后)。
- **Banner 圆角**:满屏 + 后台 banner_radius 圆角时四角露 page-bg(漂浮感);要贴边无圆角,后台设直角。
- **hover = active 视觉**:`.resource-filter:hover` 与 `.is-active` 都是 primary 底白字,悬停即高亮成主题色块。
- ⚠️ 本机无浏览器,满屏 margin trick + overflow-x clip 待部署实测:① Banner 占满屏宽 ② 无横向滚动条 ③ 顶栏仍 sticky 吸顶 ④ 分类 hover 白字 ⑤ 移动端满屏正常。
- **线上 functions.php 漏传**:戳仍 6.0.34,浏览器缓存旧窄版 —— 部署本次务必连同 `functions.php` 一起传(戳变 6.0.37,缓存失效)。
- 部署:`assets/css/resources.css` + `style.css` + `functions.php`(务必);bump 6.0.36→6.0.37。Claude 只 push GitHub,部署 TD 自管。

## v6.0.38(2026-07-07)· Banner 回到 site-header 宽度(1280)+ 恢复底部 4 卡

### 背景
- TD:满屏(100vw,v6.0.37)太宽 → Banner 参考 `.site-header__inner` 宽度(`max-width: var(--site-width)` 1280 居中,不占满屏);底部再加回 4 个资源卡横排。
- 即回到 v6.0.35 的布局(满宽 1280 + 4 卡),保留 v6.0.37 的分类 hover 白字。

### 改动
- `git checkout afdce87 -- onedong/inc/resources.php onedong/assets/css/resources.css` 恢复 v6.0.35 的两文件:Banner `max-width:var(--site-width) + margin:auto`(= `.site-header__inner` 宽度,1280 居中)+ `onedong_resource_banner_cards()` + `.resource-banner__cards`(4 卡横排,column 布局)。
- 保留 `.resource-filter:hover` 白字(v6.0.37 加的,afdce87 没有,手动补回)。
- 撤销 v6.0.37 的满屏(`width:100vw` / margin trick)与 `body{overflow-x:clip}`(1280 不满屏,无需防横向滚动)。
- 版本 6.0.37→6.0.38-ProMax。

### 坑 / 注意
- **状态 ≈ v6.0.35 + hover 白字**:Banner 1280 居中(= header inner 宽度)+ 底部 4 卡 + 分类 hover 白字。
- **Banner vs header inner 宽度**:两者都 `max-width:var(--site-width)` + `margin:auto`,外框宽一致(1280)。Banner padding 1.25rem、inner padding clamp,视觉左右外框对齐。
- v6.0.36(删 4 卡)/ v6.0.37(满屏)被本次覆盖;历史 DEV_NOTES 保留。
- ⚠️ 待部署实测:① Banner = header 宽度(1280,不占满屏)② 底部 4 卡横排 ③ 分类 hover 白字 ④ 平板 / 移动 2×2。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.37→6.0.38。Claude 只 push GitHub,部署 TD 自管。

## v6.0.39(2026-07-07)· 移除 Banner 底部 4 卡(保留 1280 宽度 + hover 白字)

### 背景
- TD:去掉 Banner 底部精选 4 卡(按权重前 4);Banner 宽度保持 1280(header 宽度)+ 分类 hover 白字不变。

### 改动
- `git checkout 2858da8 -- onedong/inc/resources.php onedong/assets/css/resources.css` 恢复 v6.0.36 的两文件:删除 `onedong_resource_banner_cards()` 函数及调用、`.resource-banner__cards` 样式与响应式;`.resource-banner` 恢复 `align-items:center` 单标题居中(padding 2rem)。
- **保留** v6.0.37/38 的:Banner 1280 宽度(`max-width:var(--site-width)`,= header 宽度,非满屏)、`.resource-filter:hover` 白字(2858da8 是 primary,手动补回白字)。
- 版本 6.0.38→6.0.39-ProMax。

### 坑 / 注意
- **状态 = v6.0.36 + hover 白字**:Banner 1280 居中 + 单标题 + 分类 hover 白字,无 4 卡。
- **Banner 宽度策略最终落点**:1280(`.site-header__inner` 宽度),非满屏(v6.0.37 满屏已撤销)。
- v6.0.38(加 4 卡)被本次覆盖;历史 DEV_NOTES 保留。
- ⚠️ 待部署实测:① Banner 1280 宽 ② 无 4 卡 ③ 分类 hover 白字。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.38→6.0.39。Claude 只 push GitHub,部署 TD 自管。

## v6.0.40(2026-07-07)· 精准移除 Banner 4 卡(保留 v6.0.38 的 Banner 布局)

### 背景
- TD:v6.0.39 用 `git checkout 2858da8` 删 4 卡时,连带把 Banner 布局从 `column`(padding 2.5rem)改成了 `align-items:center`(padding 2rem)——「其他也变了」。TD 要:**只删 4 卡**,Banner 宽度 / 布局 / hover 全部保持 v6.0.38 不变。

### 改动
- `git checkout e84e65f -- onedong/inc/resources.php onedong/assets/css/resources.css` 先恢复 v6.0.38(1280 + column + padding 2.5rem + 4 卡 + hover 白字)。
- 再**只删 4 卡**,Banner `.resource-banner`(column / padding 2.5rem / gap / 1280)原样保留:
  - `inc/resources.php`:删 `onedong_resource_banner()` 内的 `onedong_resource_banner_cards()` 调用 + 删 `onedong_resource_banner_cards()` 函数整体。
  - `assets/css/resources.css`:删 `.resource-banner__cards` 主样式块 + 1100 / 768 响应式中的 cards 规则。
- 版本 6.0.39→6.0.40-ProMax。

### 坑 / 注意
- **与 v6.0.39 的区别**:v6.0.39 连 Banner 布局一起改了(column → align-items center,padding 2.5 → 2rem);v6.0.40 只删 4 卡,Banner 保持 v6.0.38 的 column + padding 2.5rem。
- **Banner column + 无 4 卡**:column 布局本为容纳「标题 + 4 卡」,删卡后只剩标题(column + justify-content:center 仍居中),padding 2.5rem 保留(比 v6.0.39 的 2rem 略大)。若觉空可再调。
- ⚠️ 待部署实测:① Banner = v6.0.38 布局(1280 + padding 2.5rem)② 无 4 卡 ③ hover 白字不变。
- 部署:`inc/resources.php` + `assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.39→6.0.40。Claude 只 push GitHub,部署 TD 自管。

## v6.0.41(2026-07-07)· Banner 加宽到 1600 + 资源网格 PC 固定 4 列

### 背景
- TD:Banner 继续变宽(1280 → 1600);资源网格目前 2 个,要 4 个。
- AskUserQuestion 定向:Banner = 1600(比 header 1280 宽,超出内容区,大 hero 感)。

### 改动(`assets/css/resources.css`)
- **Banner 加宽**:`.resource-banner` `max-width: var(--site-width, 1280px)` → `max-width: 1600px`(margin auto 居中,比 header / main 内容区 1280 宽,两侧超出)。`.resource-banner__inner` 保持 max-width site-width(1280)→ 标题区在 1600 Banner 内 1280 居中(标题不拉满,可读)。
- **网格 4 列**:`.resource-grid` `repeat(auto-fill, minmax(240px, 1fr))` → `repeat(4, minmax(0, 1fr))`(PC 固定 4 列,不再 auto-fill)。
- `.resources-main` 不动(仍 1280):Banner 1600 超出内容区,符合 TD 选择;网格在 main 1280 内 4 列。
- 版本 6.0.40→6.0.41-ProMax。

### 坑 / 注意
- **Banner(1600)> main(1280)**:Banner 比下方内容区 / 网格宽,两侧超出 main,hero 突出。若要 Banner 与网格对齐,需 main 也加宽到 1600(本期不做,TD 选了 Banner 超 content)。
- **标题限宽**:`.resource-banner__inner` max-width 1280,Banner 1600 内标题区 1280 居中(标题不拉满 1600,保持可读)。
- **网格 PC 4 列**:`repeat(4, minmax(0,1fr))` 固定 4 列,main 1280 内每列约 300px。平板(@media 1100)仍 2 列、移动(@media 768)1 列,响应式不变。若 TD 视口在 769–1100 看到 2 列,是平板断点;要平板也 4 列需改 @media 1100(本期不改)。
- ⚠️ 待部署实测:① Banner 1600 宽(比 header 宽)② 网格 PC 4 列 ③ 标题居中可读。
- 部署:`assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.40→6.0.41。Claude 只 push GitHub,部署 TD 自管。

## v6.0.42(2026-07-07)· 资源卡片默认等高 + 确认 hover 展开介绍

### 背景
- TD:资源卡片默认大小保持一致(等高);名称下介绍文字 hover 时展示完整。
- ui-ux-pro-max 指导:hover 触屏不工作(High,渐进增强即可)、hover 要有视觉反馈(Medium)。

### 改动(`assets/css/resources.css`)
- **卡片等高**:`.resource-grid` `align-items: start`(v6.0.34 不等高,避免 hover 撑高同行)→ `align-items: stretch`(同行卡片高度一致,默认等高)。
- **hover 展开介绍**:沿用 v6.0.34 实现(`.resource-card__desc` 默认 `line-clamp:2 + max-height:3.2em`,hover `line-clamp:unset + max-height:40em` + 文字加深;`.resource-card:hover{z-index:2}` 提升层级)。已存在,未改。

### 坑 / 注意
- **等高 + hover 展开的 trade-off**:stretch 等高下,hover 一卡展开描述会撑高整行(同行 4 卡 stretch 到展开卡高度,未展开的卡底部留白)。这是「默认等高」的必然代价 —— v6.0.34 用 start 不等高来避免撑高,但卡片不齐;v6.0.42 要等高,恢复撑高。若 TD 嫌 hover 撑高同行抖动,可改回 start(不等高)或用浮出方案(absolute,复杂)。
- **触屏**:hover 展开仅 `@media (hover:hover)`,触屏看 2 行截断(渐进增强,卡片可点击访问,desc 非关键)。
- **reduced-motion**:desc transition 已在 reduced-motion 下禁用。
- ⚠️ 待部署实测:① 卡片默认等高 ② hover 展开完整介绍 ③ hover 撑高同行是否可接受。
- 部署:`assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.41→6.0.42。Claude 只 push GitHub,部署 TD 自管。

## v6.0.43(2026-07-07)· resources-main 水平 padding 统一 clamp,header 与内容区对齐

### 背景
- TD:header 的 logo 对齐内容区 / 底部最左,nav 对齐中间栏左,toggle 对齐右栏右。
- 诊断:`.site-header__inner` / `.site-footer__inner` / `.site-content--three-col` 都用 `padding: clamp(1rem,4vw,2rem)` + grid 16/1fr/16(三栏页 header 与 site-content 完全一致,本就对齐)。**唯独资源页 `.resources-main` 水平 padding 是固定 `1.25rem`**,与 header 的 clamp 不一致(大屏 header 2rem vs main 1.25rem,差 0.75rem)→ 资源页 header 的 logo/toggle 不贴 main 左 / 右。

### 改动(`assets/css/resources.css`)
- `.resources-main` 水平 padding `1.25rem` → `clamp(1rem, 4vw, 2rem)`,与 `.site-header__inner` / footer / site-content 一致 → 资源页 header logo(第一列左 = content 左)贴 main 左,toggle(第三列右 = content 右)贴 main 右。
- nav 在 header 第二列(`justify-self:start`,贴中栏左):三栏页(首页 / 文章)与 site-content 中栏对齐(已对齐);资源页单栏,nav 在 logo 右 16rem + gap 处(三栏布局固定位置)。
- 版本 6.0.42→6.0.43-ProMax。

### 坑 / 注意
- **三栏页(首页 / 文章)无需改**:header__inner 与 site-content--three-col 都是 clamp + 16/1fr/16 + 1.5rem gap,本就对齐(logo 左栏 / nav 中栏 / toggle 右栏)。
- **资源页单栏**:无中栏 / 右栏概念,nav 在 header 第二列(三栏位置),toggle 贴 content 右。改 main padding 后 logo/toggle 贴 main 左 / 右。
- **Banner(1600)不受影响**:Banner 独立宽 + 独立 padding,不与 main 对齐(Banner 比 main 宽,v6.0.41 TD 选择)。
- ⚠️ 待部署实测:资源页 header logo/toggle 与 main 网格左右对齐。
- 部署:`assets/css/resources.css` + `style.css` + `functions.php`;bump 6.0.42→6.0.43。Claude 只 push GitHub,部署 TD 自管。

## v6.0.44(2026-07-07)· header 改 flex 布局(参考 inkspire):logo+导航靠左、toggle 靠右

### 背景
- TD:OneDong header 参考 inkspire(169.html)的对齐方式 —— logo+导航靠左、深浅色 toggle 靠右(flex)。
- 169.html 是 inkspire 主题(不同主题),其 header:`.header-inner` flex space-between,`.header-left`(logo + 导航 gap 48px)靠左、`.header-right`(toggle + 订阅)靠右。
- AskUserQuestion 定向:布局改 flex(logo+导航靠左、toggle 靠右),替代 OneDong 原 grid 三栏。

### 改动(`assets/css/layout.css`)
- `.site-header__inner`:`display: grid + grid-template-columns: 16rem/1fr/16rem + column-gap` → `display: flex + align-items:center + gap:1.5rem`。3 个子(`.site-brand` logo / `.primary-nav` 导航 / `.header-controls` toggle)flex 排列。
- `.header-controls`:加 `margin-left: auto`(推到右侧)→ logo + 导航靠左(gap 1.5rem),toggle 靠右。与 inkspire `.header-left` / `.header-right` space-between 效果一致。
- 不改 `header.php`(3 子结构用 CSS flex 即可);不改 padding(仍 clamp,响应式);不照搬 inkspire 的渐变 logo / 订阅按钮(OneDong 用图片 logo,无订阅)。
- 版本 6.0.43→6.0.44-ProMax。

### 坑 / 注意
- **放弃三栏对应**:原 grid 三栏(logo 16rem 对齐左作者卡 / nav 中栏 / toggle 16rem 对齐右侧栏)改为 flex 后,header 不再和 site-content 三栏逐栏对齐。TD 选了 flex(参考 inkspire),接受此变化。
- **logo+导航靠左**:flex gap 1.5rem,logo 和导航紧邻(inkspire gap 48px ≈ 3rem,这里用 1.5rem 协调 OneDong 间距)。
- **≤1180 已是 flex**:`@media 1180` 的 `.site-header__inner{display:flex}` 之前就有(汉堡菜单),本次 PC(>1180)也改 flex,全屏一致 flex。
- **padding 仍 clamp**:logo 贴 clamp 左(= 内容区左),toggle 贴 clamp 右(= 内容区右),与 resources-main / site-content / footer 对齐。
- ⚠️ 待部署实测:① logo+导航靠左 ② toggle 靠右 ③ 各页 header 一致 ④ 移动端汉堡正常。
- 部署:`assets/css/layout.css` + `style.css` + `functions.php`;bump 6.0.43→6.0.44。Claude 只 push GitHub,部署 TD 自管。

## v6.0.45(2026-07-07)· header 回到 grid 三栏(撤销 v6.0.44 flex):三栏对齐

### 背景
- TD:首页 logo 对齐左栏左、导航对齐中栏左、太阳对齐右栏右 —— 即**三栏对齐**(header 与 site-content 三栏逐栏对齐)。
- v6.0.44 改 flex(logo+导航靠左、toggle 靠右)不是三栏对齐,撤销回 grid 三栏。

### 改动
- `git checkout 1150e60 -- onedong/assets/css/layout.css` 恢复 v6.0.43 的 layout.css:`.site-header__inner` grid `16rem/1fr/16rem` + clamp padding;`.header-controls` `justify-content:flex-end`(无 margin-left auto)。
- grid 三栏 == `.site-content--three-col`(`16rem/1fr/16rem` + clamp + 1.5rem gap):logo 第一列(`justify-self:start`,贴左栏左)、nav 第二列(贴中栏左)、toggle 第三列(`flex-end`,贴右栏右)。三栏逐栏对齐。
- 版本 6.0.44→6.0.45-ProMax。

### 坑 / 注意
- **三栏对齐 = grid**:header grid 三栏与 site-content 三栏完全一致(max-width site-width + clamp padding + 16/1fr/16 + 1.5rem gap),logo / nav / toggle 逐栏对齐左栏 / 中栏 / 右栏。v6.0.44 flex 不满足(logo+nav 挤左)。
- **之前「首页没对齐」是 CDN**:首页 HTML 之前缓存 6.0.4(ver 戳 6.0.4),加载旧 CSS。grid 代码本就对齐,刷首页 HTML CDN(ver 变 6.0.45)即对齐。
- **资源页单栏**:header grid 三栏 vs resources-main 单栏(无中栏),nav 在第二列(logo 右 16rem)。资源页 logo/toggle 贴 main 左/右(v6.0.43 resources-main clamp),nav 在 16rem 处。
- ⚠️ 待部署实测:首页 logo / 导航 / toggle 与三栏(左作者卡 / 中文章 / 右侧栏)逐栏对齐。
- 部署:`assets/css/layout.css` + `style.css` + `functions.php`;bump 6.0.44→6.0.45。**务必刷首页 HTML CDN(全站目录 `/`)**。
- 部署:`assets/css/resources.css` + `style.css` + `functions.php`;bump `ONEDONG_VERSION` 刷 `?ver=`;刷腾讯云 CDN + 浏览器硬刷新。

## v6.0.46(2026-07-07)· 移动端 ≤768 logo 与栏目左边对齐(修三层 padding 叠加)

### 背景
- TD:首页 logo 左边对齐下面栏目左边;导航对齐中间栏左边。

### 改动
- `assets/css/layout.css` 768px 断点:
  - `.site-header__inner` 横向 padding `1rem` → `0.75rem`(对齐 `.site-main`)。
  - `.site-content--three-col` 加 `padding: 0`(去掉自身 clamp ~1.9rem,改吃主区 0.75rem)。
- 移动端三层 padding 收敛成 0.75rem 基准线:logo 左 == 文章卡/作者卡左 == site-main 左。
- 版本 6.0.45→6.0.46-ProMax。

### 坑 / 注意
- **只动了移动端(≤768)**:桌面 grid 三栏本就共线(v6.0.45 已确认 logo/nav/toggle 逐栏对齐 site-content),未改桌面 CSS。
- **导航对齐中间栏 = CDN 嫌疑**:v6.0.45 已查明「首页没对齐」是首页 HTML 缓存旧 CSS(ver 戳旧版),grid 代码本就对齐。本次 TD 再提导航对齐,桌面端代码无需改,大概率仍是 CDN 未刷 → 刷腾讯云 CDN(全站目录 `/`)+ 浏览器硬刷新即可。
- **移动端三层 padding 叠加**(本次修):≤768 时 `.site-main`(0.75rem)是 `.site-content--three-col` 父级,后者自身又带 clamp padding,叠加后内容比 header(1rem)偏右 ~1.7rem。修法:统一 0.75rem,去掉 content 自身 padding。
- 部署:`assets/css/layout.css` + `style.css` + `functions.php`;bump 6.0.45→6.0.46。刷腾讯云 CDN(CSS + 首页 HTML)+ 浏览器硬刷新。

## v6.0.47(2026-07-08)· logo 与左侧作者卡左边对齐(桌面端,改 CSS 不换图)

### 背景
- TD:首页 logo 最左边要和左侧作者卡最左边对齐;顺带导航↔中文章流、主题按钮↔右侧栏右边也要对齐。

### 排查(关键结论,勿重走弯路)
- 顶栏 `.site-header__inner` 与内容区 `.site-content--three-col` 的 grid 配置**逐字一致**(`16rem 1fr 16rem` + 同 padding `clamp(1rem,4vw,2rem)` + gap 1.5rem),左右缘**本就共线**。
- 故**导航↔文章流、主题按钮↔右栏右边本就对齐**,无需动。
- logo 偏移**唯一根因 = logo 图自带透明左留白**:`cropped-logo.png` 原图 1721×458,左边 128px 全透明(7.4%);顶栏等比缩放后留白 ≈ 11px → logo 可见内容比其容器(=作者卡左缘)缩进 11px。**图的问题,非布局问题。**
- 像素测量坑:卡片 `--shadow` 仅 rgb 1% 透明、边框 `--line:#F2F3F5` 极浅(sum≈730),常规阈值测不到卡真实左缘,易误判偏移 40+px;需用深色文字/图标边缘作锚。

### 改动(用户选「改代码不换图」)
- `assets/css/layout.css` `.site-brand img`:`max-height:38; height:auto` → `height:38px`(固定显示高度,任意 logo 锁此尺寸、宽按比例自适应)+ `margin-left:-11px`(抵消当前 logo 透明留白,可见左边与作者卡共线)。
- `style.css`:版本号 6.0.46→6.0.47(刷 CSS 缓存)。
- commit `4b27577`,push main。

### 坑 / 注记
- `margin-left:-11px` 按**当前 logo 图**留白算(128/1721 × 38/458 ≈ 11px);换不同留白的 logo 需重测微调。
- 治本方案 = 裁掉 logo 图透明边(Pillow `getbbox()` 已生成 `logo-trimmed.png` 1478×377,用户未采用)。
- 部署后需强刷(Ctrl+F5)+ 清腾讯云 CDN(全站目录 `/`),否则旧 CSS 缓存。

## v6.0.48(2026-07-08)· 三栏对齐修正:logo 换图后重测留白 + 导航文字对齐(非仅盒对齐)

### 背景
- TD:首页 logo 对齐左侧作者卡左缘、导航菜单对齐中间文章流左缘、主题按钮对齐右栏右缘;所有三栏布局页(含单篇 169.html)统一此对齐。

### 排查(两个独立根因,勿再当成一个)
1. **logo 又偏 = 换过图,旧 -11px 失配**:v6.0.47 的 `-11px` 是按当时 logo(1721×458)算的。现线上 logo 已换成 `cropped-dingxd_logo_transparent-scaled-1.png` **2559×681**,Pillow `getbbox()` 实测不透明区 bbox=(269,112,2324,557) → 透明左留白在 38px 显示高度下 ≈**15px**(右13/上6.3/下6.9)。旧 -11px 欠补 ~4px,logo 右偏。
2. **导航「文字」从未对齐,只是「盒」对齐**:v6.0.45/47 笔记认定「导航↔文章流本就对齐,无需动」——**只对到 nav 盒左缘**(`justify-self:start` = 中列左缘)。但 `.primary-nav a` 有 `padding-left:0.75rem`,首个链接**文字**比中列左缘(=文章卡外框左缘=「文章流左侧」)内缩 12px。TD 要的是文字对齐,非盒对齐。

### 改动(TD 选方案 B:只改 CSS 不换图)
- `assets/css/layout.css`:
  - `.site-brand img` `margin-left` **-11px → -15px**(匹配当前 2559×681 logo 的 15px 透明左留白)。
  - `.primary-nav` 加 `margin-left:-0.75rem`(负边距抵消首链接 padding-left,使菜单文字左缘 = 中列左缘 = 文章卡外框左缘)。
  - `@media(max-width:1180px)` 加 `.primary-nav{margin-left:0}`(顶栏转 flex + 左栏隐藏,复位)。
  - `@media(max-width:768px)` `.primary-nav` 加 `margin-left:0`(fixed 抽屉复位,否则被推出屏幕左缘)。
- `style.css` 6.0.47→6.0.48;`functions.php` `ONEDONG_VERSION` **6.0.46→6.0.48**(此前停在 6.0.46,比 style.css 还旧;资源 `?ver=` 用的是它,不 bump 则 CSS 缓存不刷)。
- 主题按钮(小太阳):`.header-controls justify-content:flex-end` 已贴右列右缘,标尺仅差 ~1px(边框/亚像素),**未动**。

### 覆盖范围
- 顶栏是全站唯一 `header.php`,故三处对齐**全站生效**。三栏页(`home/archive/search/single/archive-onedong_moment/single-onedong_moment` 均用 `.site-content--three-col`,与顶栏 grid 逐字一致)自动共用同一基线。**169.html = single.php 三栏**,已覆盖。

### 坑 / 注记
- `-15px` 仍按**当前 logo** 留白算,**换 logo 必重测**(`from PIL import Image; Image.open(f).getbbox()` → 透明边×(38/图高))。这是方案 B 的固有脆弱点。
- 治本 = 裁掉透明像素的方案 A:已生成 `dingxd_logo_trimmed.png`(2055×445,存 TD 桌面),TD 重传后可将 `margin-left` 归 0 且 logo 填满 38px 更清晰;本次 TD 未采用。
- `ONEDONG_VERSION` 与 `style.css` 版本号此前长期不同步(6.0.46 vs 6.0.47),本次拉平到 6.0.48,后续 bump 记得两处一起。
- 部署后强刷 + 清腾讯云 CDN(CSS 与首页/单篇 HTML),否则读旧缓存(v6.0.45 的「以为没对齐」即缓存所致)。
