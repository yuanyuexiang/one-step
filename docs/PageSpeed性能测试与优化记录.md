# PageSpeed 性能测试与优化记录

测试日期：2026-11-13（北京时间）。

**后续变更：** 本文测试完成后，用户已明确要求开放收录，生产站 `blog_public=1` 已生效并通过线上检查。下文 SEO 69 分和 `noindex` 描述属于测试当时的历史状态，不代表当前仍禁止收录。目标：生产站 https://ai-onestep.com 。官方 PageSpeed Insights 网页，Lighthouse 13.6.0；移动端模拟 Moto G Power、低速 4G。无登录状态，不提交表单。

免密钥 PageSpeed API 返回 HTTP 429（配额为 0），因此改用官方网页实测。以下是实际报告，不是本地 Lighthouse 分数，也不是预估分数。全部报告目前没有 CrUX 真实用户数据，不能据此声称真实用户 Core Web Vitals 已通过。

## 第一轮基线

每个页面各一次移动端和桌面端；性能分数可能随测试环境波动。

| 页面 | 移动性能 | 桌面性能 | 移动 LCP | 桌面 LCP | 移动 CLS | 桌面 CLS | 无障碍（移动/桌面） |
|---|---:|---:|---:|---:|---:|---:|---:|
| 中文首页 | 96 | 100 | 2.285 s | 0.482 s | 0 | 0 | 86 / 92 |
| 英文首页 | 95 | 100 | 2.305 s | 0.499 s | 0 | 0 | 86 / 92 |
| 英文折页菜单详情 | 94 | 100 | 2.795 s | 0.406 s | 0 | 0 | 91 / 96 |
| 合作相册 | 98 | 100 | 0.945 s | 0.441 s | 0 | 0 | 89 / 96 |
| 视频资料 | 98 | 91 | 1.745 s | 0.416 s | 0.09 | 0.19 | 89 / 96 |
| 联系页 | 100 | 100 | 0.937 s | 0.268 s | 0 | 0 | 91 / 96 |

全部页面最佳做法 100 分、SEO 69 分。SEO 扣分项为当前明确保留的 `noindex`；内容业务审核完成前不为提分擅自开放收录。TBT 除英文首页移动端 4 ms 外均为 0 ms；它是实验室指标，不能替代真实用户 INP。

原始报告（链接内可切换手机/桌面）：

- [中文首页](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/gmj704c6mu?form_factor=mobile)
- [英文首页](https://pagespeed.web.dev/analysis/https-ai-onestep-com-en/5lj6tuiush?form_factor=mobile)
- [英文折页菜单详情](https://pagespeed.web.dev/analysis/https-ai-onestep-com-en-products-legacy-3e69537cbf/vw89mm5hll?form_factor=mobile)
- [合作相册](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-cases/vddkq3r0np?form_factor=mobile)
- [视频资料](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-videos-zh-hans/hz0b5j1aw9?form_factor=mobile)
- [联系页](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-contact/p480jzea9k?form_factor=mobile)

## 报告发现的问题及处理

1. 视频页桌面 CLS 0.19，超过良好范围；给播放器预留 16:9 画面，避免元数据加载后改变布局。
2. 相册首载约 31,059 KiB、视频页约 30,697 KiB。原 `preload="metadata"` 实际触发较大视频下载，改为 `preload="none"`，保留海报和点击播放。
3. 图片尺寸偏大、原图用于缩略图。通过显式 WP-CLI 生成 320/768/1280 宽度的 WebP，输出 `srcset`/`sizes`；原图保留，放大查看仍打开原图。索引图和 PNG 调色板格式另有隔离测试。
4. 静态资源没有明确缓存策略。仅为图片、视频、CSS、JS 等静态文件设置 30 天浏览器缓存，启用文本压缩；不缓存 WordPress HTML、后台或询盘接口。
5. 移动端隐藏可见标签导致语言 select 无可访问名称，补充独立 `aria-label`；扩大轮播分页按钮命中范围并提高辅助文字对比度。

不添加空字幕轨道来规避审计。原视频尚无经核对字幕，字幕仍是后续内容工作；自动无障碍分数不能代替人工检查。实验性的智能体浏览分数也不等于 GEO 获得推荐或收录。

## 验证与发布

本地检查包括七语全站回归、PHP/JavaScript 语法、Compose 隔离、静态资源缓存响应，以及隔离数据库中的 WebP 生成、调色板 PNG、重复执行、原图不变验证。生产通过现有 Actions 发布，先备份，再生成派生图；不会在访客请求中压图，也不修改 Beacon。

优化后生产复测结果在下方补充。

## 优化后生产复测

已部署应用版本：`163d0bb70cf032057a4caef633d673c749ab9a0b`。[GitHub Actions](https://github.com/yuanyuexiang/one-step/actions/runs/43449278108) 的检查、构建、备份发布与公网验证全部成功。以下为同日优化后第一轮双端复测，各页各设备一次：

| 页面 | 移动性能 | 桌面性能 | 移动 LCP | 桌面 LCP | 移动/桌面 CLS | 无障碍（移动/桌面） |
|---|---:|---:|---:|---:|---:|---:|
| 中文首页 | 100 | 100 | 1.801 s | 0.401 s | 0 / 0 | 96 / 96 |
| 英文首页 | 100 | 100 | 1.829 s | 0.401 s | 0 / 0 | 96 / 96 |
| 英文折页菜单详情 | 100 | 100 | 1.367 s | 0.336 s | 0 / 0 | 96 / 96 |
| 合作相册 | 100 | 100 | 0.936 s | 0.441 s | 0 / 0 | 100 / 100 |
| 视频资料 | 97 | 100 | 2.072 s | 0.419 s | 0 / 0 | 100 / 100 |
| 联系页 | 100 | 100 | 0.947 s | 0.263 s | 0 / 0 | 96 / 96 |

本轮 TBT 全部为 0 ms；最佳做法全部 100，SEO 仍为 69。视频移动性能 98→97，LCP 1.745→2.072 s，不能声称所有单项都提高；桌面 CLS 0.19→0、总下载量大幅下降是本次明确改善。尚有图片传送及部分文字对比度建议，未宣称所有审计项全部通过。

复测报告：

- [中文首页](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/47wb880qsw?form_factor=mobile)
- [英文首页](https://pagespeed.web.dev/analysis/https-ai-onestep-com-en/2xd0rhiaf2?form_factor=mobile)
- [英文折页菜单详情](https://pagespeed.web.dev/analysis/https-ai-onestep-com-en-products-legacy-3e69537cbf/bqcwd5x2r4?form_factor=mobile)
- [合作相册](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-cases/tsf8tk8d64?form_factor=mobile)
- [视频资料](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-videos-zh-hans/qmp3tsnss4?form_factor=mobile)
- [联系页](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-contact/t3pqpc5xm3?form_factor=mobile)

### 下载量与缓存

桌面端报告“避免网络负载过大”中的总大小：

| 页面 | 基线 | 优化后 | 降幅 |
|---|---:|---:|---:|
| 合作相册 | 31,059 KiB | 381 KiB | 约 98.8% |
| 视频资料 | 30,697 KiB | 228 KiB | 约 99.3% |

生产另做六页只读验证：视频全部延后加载；语言选择器有独立可访问名称；视频库预留尺寸；抽取的 72 个 WebP 预览地址全部正常并带静态缓存响应；CSS 的 gzip 压缩正常。HTML 未误设为 30 天静态缓存。未向生产提交测试询盘。

### 首页追加复验

中文首页共观察到三组优化后报告，避免只取最高分：

| 样本 | 移动性能 | 移动 LCP | 移动 TBT | 移动 CLS | 桌面性能 |
|---|---:|---:|---:|---:|---:|
| 上表首轮 | 100 | 1.801 s | 0 ms | 0 | 100 |
| [追加 1](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/htypnbpnze?form_factor=mobile) | 100 | 1.506 s | 0 ms | 0 | 100 |
| [追加 2](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/ucrzeu2cu0?form_factor=mobile) | 96 | 1.857 s | 6 ms | 0 | 100 |

移动性能范围 96–100，中位数 100；LCP 中位数 1.801 s。不同样本的 Speed Index 有波动，所以不把一次 100 分理解为所有访客、所有网络下恒定满分。其他五页本轮各做一次双端复测，没有将其伪称为三次中位数。

后续应在真实流量积累后跟踪 CrUX/实际访问表现，并在首页结构、图片、插件或客服加载方式发生明显变化后复测。当前无需为分数继续删减业务交互。

## UI 改版后重新测试（2026-11-14）

测试对象为已上线应用版本 `33da1aa93e8f8f0ddba7ea0d775d0d2af9d0713e`，对应[视觉改版发布流水线](https://github.com/yuanyuexiang/one-step/actions/runs/43555686379)。北京时间 14:10–14:17 在官方 PageSpeed Insights 网页发起测试，Lighthouse 13.6.0；手机报告显示模拟 Moto G Power、低速 4G，桌面使用对应桌面配置。测试服务以未登录访客方式访问生产站，不提交询盘。

**结论：桌面测试均为 100，首轮六页手机性能为 97–100，SEO／无障碍／最佳做法均为 100。但中文首页追加测试出现 85 分、CLS 0.296，发现偶发的移动端布局偏移，不能以首轮全部 CLS 为 0 声称新版始终稳定。**

### 六页首轮结果

每页各一次手机与桌面报告。LCP 秒数根据报告计算器链接的毫秒值记录到三位小数；TBT 按报告指标区域的显示值记录。

| 页面 | 手机性能 | 桌面性能 | 手机 LCP | 桌面 LCP | 手机 TBT | 手机／桌面 CLS | 无障碍（手机／桌面） |
|---|---:|---:|---:|---:|---:|---:|---:|
| 中文首页 | 98 | 100 | 1.398 s | 0.352 s | 0 ms | 0 / 0 | 100 / 100 |
| 英文首页 | 100 | 100 | 1.374 s | 0.281 s | 0 ms | 0 / 0 | 100 / 100 |
| 英文折页菜单详情 | 98 | 100 | 1.370 s | 0.339 s | 0 ms | 0 / 0 | 100 / 100 |
| 合作相册 | 98 | 100 | 0.979 s | 0.441 s | 10 ms | 0 / 0 | 100 / 100 |
| 视频资料 | 97 | 100 | 2.072 s | 0.361 s | 0 ms | 0 / 0 | 100 / 100 |
| 联系页 | 100 | 100 | 0.933 s | 0.267 s | 0 ms | 0 / 0 | 100 / 100 |

以上十二份设备结果的最佳做法和 SEO 均为 100，桌面 TBT 均显示 0 ms。相册手机计算器链接中的原始 TBT 为 14 ms，指标区域四舍五入显示 10 ms，并非两次测试。

原始报告（同一链接可切换手机／桌面）：

- [中文首页，14:10:47](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/45mqaarw6o?form_factor=mobile)
- [英文首页，14:11:09](https://pagespeed.web.dev/analysis/https-ai-onestep-com-en/vcbgoh14rd?form_factor=mobile)
- [英文折页菜单详情，14:11:41](https://pagespeed.web.dev/analysis/https-ai-onestep-com-en-products-legacy-3e69537cbf/5jluf82k2s?form_factor=mobile)
- [合作相册，14:12:11](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-cases/dwan7plcll?form_factor=mobile)
- [视频资料，14:12:34](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-videos-zh-hans/a738qb6qy9?form_factor=mobile)
- [联系页，14:12:54](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans-contact/x3dqseuv9s?form_factor=mobile)

### 中文首页追加复验：必须保留异常样本

| 样本 | 手机性能 | 手机 LCP | 手机 TBT | 手机 CLS | 手机 Speed Index | 桌面性能 |
|---|---:|---:|---:|---:|---:|---:|
| 首轮，14:10:47 | 98 | 1.398 s | 0 ms | 0 | 3.942 s | 100 |
| [追加 1，14:16:57](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/29n7wu5pdy?form_factor=mobile) | 98 | 1.486 s | 0 ms | 0 | 4.215 s | 100 |
| [追加 2，14:17:21](https://pagespeed.web.dev/analysis/https-ai-onestep-com-zh-hans/aqyq0agaly?form_factor=mobile) | 85 | 1.368 s | 0 ms | **0.296** | 2.050 s | 100 |

三次手机性能范围 **85–98，中位数 98**，LCP 中位数 1.398 秒。不能删除 85 分样本，也不能只展示中位数而略过布局问题。第三次 LCP 与 Speed Index 并不慢，掉分主要对应 CLS 异常；这与前两次主要受 Speed Index 影响的 98 分情况不同。

### 与改版前记录对比

| 项目 | 改版前优化后首轮 | 本次首轮 | 判断 |
|---|---|---|---|
| 中文首页手机性能 | 100；此前三次范围 96–100 | 98；本次三次范围 85–98 | 新发现低分样本，需要优先排查布局稳定性 |
| 中文首页手机 LCP | 1.801 s | 1.398 s | 本次首轮更快，但不能抵消 CLS 问题 |
| 英文首页手机性能／LCP | 100 / 1.829 s | 100 / 1.374 s | 本次性能分数维持，LCP 更短 |
| 折页菜单详情手机性能 | 100 | 98 | 本次仍有图片与渲染阻塞建议，不能说所有页面均提升 |
| 合作相册手机性能 | 100 | 98 | 本次有少量主线程阻塞，仍需结合重复样本判断 |
| 视频资料手机性能 | 97 | 97 | 本次分数维持 |
| SEO | 当时禁止收录，69 | 本次全部 100 | 开放收录后，相关审计扣分已消除；不等于已有排名或被收录 |
| 无障碍 | 首页、详情、联系页为 96 | 本次所测页面全部 100 | 自动审计改善，仍不能替代人工无障碍验收 |

### 优先问题与初步定位

**P0：移动端偶发布局偏移，尚未修复。**

在 85 分报告展开“布局偏移原因”后，报告把 **0.296** 的偏移归到 `body.home > main#main.design-home`，同时提示页脚 `.brand-logo` 图片的尺寸问题。这里识别的是被移动元素和可能原因，不能仅凭列表就断定页脚图片是唯一根因。Google 也说明该诊断可能只给出原因推测，完整归因需要结合性能轨迹：[布局偏移原因说明](https://developer.chrome.com/docs/performance/insights/cls-culprit)。

代码检查发现一个需要优先验证的机制：

- [site.js](../wp-content/themes/xdj-site/assets/site.js) 在脚本执行时才添加 `nav-ready` 类。
- [design.css](../wp-content/themes/xdj-site/assets/design.css) 在手机宽度下通过该类隐藏原导航、显示折叠菜单按钮。
- 如果浏览器先绘制完整导航，随后才执行脚本，页头高度会改变，从而带动整个 `main` 上移。这与报告中主内容整体偏移的表现一致，是**较强嫌疑，尚非通过修复前后实验验证的最终根因**。

建议修复时让手机导航首帧布局与初始化后保持一致，同时保留无 JavaScript 时可访问的导航；检查标识图片的实际渲染宽高与预留比例。随后在相同版本上重新做至少三次中文手机首页，并覆盖英文及一个长文案语言。不能通过隐藏主内容或移除菜单来规避审计。

**P1：继续优化图片传送及渲染阻塞。**

首轮手机报告给出的图片预计节省量：中文首页 94 KiB、英文首页 96 KiB、详情 125 KiB、相册与视频各 188 KiB、联系页 56 KiB。中文首页首轮渲染阻塞预计可缩短约 450 ms，异常样本约 420 ms。这些是工具估算值，不是已实现收益，优化项之间也不能简单相加。

后续应核对图片显示尺寸与候选资源、压缩策略以及新旧样式的覆盖关系。仍保留清晰的原图与放大查看，不以删资料或模糊产品图换分。

### 本次范围与限制

本次完成六页双端测试及中文首页两组双端追加复验，共观察十六份设备结果。其他五页没有伪称做过三次取中位数。未测试全部七种语言，也未在本轮修改、部署网站代码；布局问题明确列为待修复。

全部报告仍显示“无任何数据”，没有 CrUX 真实用户数据。TBT 是实验室指标，不当作真实用户 INP。SEO 100、无障碍 100 和实验性的智能体浏览检查均不代表保证收录、完全可访问或 GEO 推荐成功。
