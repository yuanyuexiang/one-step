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
