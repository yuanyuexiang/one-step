# 小当家网站 Google 搜索收录操作指南

更新日期：2026 年 11 月 14 日。适用网站：`https://ai-onestep.com`。

目标：通过 Google Search Console 验证网站、提交网址并跟踪收录。**SEO 100 分不等于已收录；提交成功也不保证排名。** 本文是操作指南，尚未代表已替你完成 Google 账号或 AWS 配置。

## 一、开始前准备

准备可以长期管理网站的 Google 账号，以及现有 AWS Route 53 的管理权限。尽量由网站实际所有者持有 Google 账号；协作人员后续通过 Search Console 用户权限加入，不共享账号密码。

本次已实际检查：

- [robots.txt](https://ai-onestep.com/robots.txt) 正常返回，允许前台抓取，限制 WordPress 管理区。
- [网站地图](https://ai-onestep.com/wp-sitemap.xml) 正常返回，索引中包含七种语言的页面、产品和分类子地图。
- `/sitemap.xml` 会转向 `/wp-sitemap.xml`。下面直接提交后者。
- 此前已按授权开放生产站收录；本地 `localhost:8080` 不是提交对象。

## 二、在 Search Console 添加网站

1. 打开 [Google Search Console](https://search.google.com/search-console/)，登录 Google 账号。
2. 在资源选择器中选择“添加资源”（首次进入通常直接显示添加页面）。
3. 选择 **“网域／域名资源（Domain）”**。
4. 输入下面的域名，不加协议、斜杠或语言路径：

   ```text
   ai-onestep.com
   ```

5. 继续，选择使用 DNS TXT 记录验证，复制 Google 给出的完整记录值。格式类似：

   ```text
   google-site-verification=这里是Google生成的真实验证码
   ```

以上为格式示例，不能原样使用。使用你自己页面上生成的值。

域名资源覆盖该域名的不同协议、子域名及路径；七种语言不必分别做 DNS 验证。它也可能显示同域名下 Beacon 等子域的数据，后续分析独立站时按页面 URL 或语言路径筛选。验证不会改动 Beacon 部署。[Google 所有权验证说明](https://support.google.com/webmasters/answer/9008080?hl=zh-Hans)

## 三、在 AWS Route 53 添加 TXT 记录

进入 [Route 53 控制台](https://console.aws.amazon.com/route53/) → **托管区域 → 现有公开托管区域 `ai-onestep.com` → 创建记录**。

填写：

| 字段 | 填写内容 |
|---|---|
| 记录名称 | 留空，表示根域名 `ai-onestep.com`；不要填 `www` 或 `zh-hans` |
| 记录类型 | `TXT` |
| 别名 | 关闭 |
| 值 | 粘贴 Google 给出的完整 `google-site-verification=...` |
| TTL | `300` 秒即可 |
| 路由策略 | 简单路由 |

Route 53 的 TXT 值按界面提示填写，通常显示为双引号包裹的一段文本，例如 `"google-site-verification=真实验证码"`；不要重复套多层引号、拆成多个验证码或加入多余空格。

**只新增验证记录，不修改原 A、NS、SOA 记录，也不修改 `beacon`、`traefik` 或证书验证记录。** 如果根域名已经有 TXT 记录，编辑现有同名 TXT 记录集，在新的一行追加 Google 值，保留所有原值，不覆盖邮件 SPF 或其他验证内容。

保存后，回到 Search Console 点击“验证”。如果暂时失败，先等待 DNS 传播，再检查名称、类型和值。AWS 表示修改通常在 60 秒内传播到 Route 53 权威服务器，但 Google 使用的解析缓存可能尚未刷新，不要据此认定一分钟必定验证成功。[AWS 创建记录说明](https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/resource-record-sets-creating.html)

可选核查命令：

```sh
dig +short TXT ai-onestep.com
```

看到相应验证值仅表示查询到了记录，最终以 Search Console 验证成功为准。验证完成后保留 TXT，Google 会持续核验所有权。

## 四、提交网站地图

1. Search Console 中选中 `ai-onestep.com` 资源。
2. 打开左侧 **“索引 → 站点地图”**，具体文字可能随界面语言变化。
3. 在新增站点地图处输入完整地址：

   ```text
   https://ai-onestep.com/wp-sitemap.xml
   ```

4. 点击“提交”。域名资源通常需要完整 URL；若界面已固定显示站点前缀，则按界面要求只填写剩余部分。
5. 在“已提交的站点地图”查看处理状态和最后读取时间。显示“成功”说明读取成功，不表示所有页面已经收录。

只需提交这个地图索引，不必逐个重复提交全部语言子地图。如果报“无法抓取”，检查地图能否公开访问、HTTPS、服务器错误及子地图状态，再重试。不要为了提交重新生成多套地图。[Google 创建和提交站点地图](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap?hl=zh-cn)

## 五、检查重点页面并请求编入索引

先处理首页及少数重要业务页，其他页面让地图和内部链接帮助发现。

1. 在 Search Console 顶部“检查网址”输入：

   ```text
   https://ai-onestep.com/zh-hans/
   ```

2. 查看索引状态。若提示网址不在 Google 上，点击 **“测试实际网址”**。
3. 查看实际测试是否允许抓取、是否允许编入索引，以及网页能否正常获取。
4. 若没有阻止索引的问题，点击 **“请求编入索引”**，等待提交完成。
5. 再对英文首页执行同样步骤：

   ```text
   https://ai-onestep.com/en/
   ```

6. 从网站正常导航进入一两个重点分类或产品，将地址栏里的最终 HTTPS 地址复制过去检查。其他语言首页按需检查：`/zh-hant/`、`/fr/`、`/de/`、`/it/`、`/es/`。

不要提交本地地址、后台地址、搜索结果、带询盘参数的链接或历史重定向地址。同一个网址短时间重复请求不会加快抓取，遇到配额限制后停止重复提交。

“实际测试通过”表示当前页面可获取，不代表已进入索引。Google 说明抓取可能需要数天至数周，请求也不保证最终收录。[Google 请求重新抓取说明](https://developers.google.com/search/docs/crawling-indexing/ask-google-to-recrawl?hl=zh-cn)

## 六、怎么看是否真正收录

优先看 Search Console **网址检查的索引结果**，再看“网页索引”报告和搜索效果数据。

| 看到的状态 | 怎么理解与处理 |
|---|---|
| 网址在 Google 上 | 已有索引资格／记录，仍不能保证每次搜索都展示或有理想排名 |
| 已发现，目前尚未编入索引 | Google 已知道该 URL，等待抓取；检查服务器稳定性、内部链接与内容价值 |
| 已抓取，目前尚未编入索引 | 已抓取但暂未选入索引；重点检查正文完整性、独特价值与重复内容，不能只重复提交 |
| 被 noindex 排除 | 检查页面及响应头是否禁止索引；搜索页等有意排除的页面无需全部开放 |
| 被 robots.txt 屏蔽 | 对应 URL 抓取受限，核对规则；管理区被屏蔽属于正常情况 |
| 网页会重定向 | 检查并提交最终目标地址，历史地址跳转通常无需单独索引 |
| 重复网页／Google 选择了其他规范网页 | 对照用户声明和 Google 选择的 canonical；核实语言内容、重复页面及重定向，不要强制所有语言指向中文首页 |
| 服务器错误／抓取失败 | 先解决 5xx、超时、证书或访问限制，再测试实际网址 |

可在 Google 辅助搜索：

```text
site:ai-onestep.com
site:ai-onestep.com/zh-hans/
ai-onestep.com 小当家
```

`site:` 结果不是完整的索引清单，搜不到也不能单凭这一点认定所有页面均未收录。Search Console 的索引报告与效果数据也有处理延迟。

## 七、建议的跟进节奏

- **当天**：完成域名验证、提交地图、检查中英文首页并提交重点页。
- **之后每隔几天**：查看地图读取情况、重点页网址检查与排除原因；有明确错误再处理。
- **持续跟进**：在“效果／搜索结果”查看展示次数、点击、查询词及页面；按语言路径筛选。

以上是操作节奏，不是收录时间承诺。搜索品牌或域名能找到，与搜索“英国菜单印刷”“餐饮收银系统”等竞争关键词获得排名不同。后者需要准确的本地化业务内容、真实产品资料、合理内链和来自真实渠道的引用。

后续完善产品参数、纸张工艺、服务范围和常见问题时，应以核实内容为准。不能为排名编造客户案例、把历史宣传数字当成当前成绩，也不购买垃圾外链或批量生成雷同页面。旧站若需要迁移搜索信号，必须在有权限控制的旧域名上配置对应重定向；新站单方面接收旧路径并不能替代旧域名跳转。

## 八、完成检查清单

- [ ] Search Console 已添加 `ai-onestep.com` 域名资源。
- [ ] Route 53 根域名 TXT 验证成功，原 DNS 记录保留。
- [ ] `https://ai-onestep.com/wp-sitemap.xml` 已提交并被成功读取。
- [ ] 中英文首页实际测试通过，必要时已请求索引。
- [ ] 至少一两个重点业务页面已检查最终 URL 与 canonical。
- [ ] 已区分“提交成功”“已收录”和“有关键词排名”。
- [ ] 已安排后续查看索引原因及展示／点击数据。

关联记录：[PageSpeed 性能测试与优化记录](PageSpeed性能测试与优化记录.md)。目前另有手机端偶发布局偏移待修复；它是体验问题，不应与“网站是否允许 Google 收录”混为一谈。
