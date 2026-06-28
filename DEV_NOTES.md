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
