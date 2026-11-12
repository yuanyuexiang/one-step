# One Step WordPress

目标：ai-onestep.com，Docker Compose，接入服务器已有 Traefik。

## 当前阶段

已建立部署和内容类型骨架。主题尚非最终页面；询盘、WPML、ACF、Gravity Forms、客服同步和权限尚未实现或配置，不能直接用于生产。

## 准备环境

1. 将 `.env.example` 复制为 `.env`，填写真实 Traefik 外部网络及 HTTPS entrypoint。
2. 将镜像变量替换为经验证的精确版本或 digest；示例浮动标签仅用于开发，禁止未经验证投入生产。
3. 创建 `secrets/db_password` 与 `secrets/db_root_password`（不同随机密码），限制目录和文件权限，不提交 Git。
4. 运行 `docker compose -f docker-compos.yaml config --quiet`，再构建并启动。先确认 Traefik 已加入指定网络。
5. 按现有证书机制补充 resolver 标签或复用已有证书；不会自动猜测 resolver。

不映射宿主机 80/443 或数据库端口，不启动第二个 Traefik。AnythingLLM 默认不公开，可通过受控临时运维通道初始化。

WordPress 安装完成后启用 One Step 主题与 One Step Business 插件；商业插件通过客户官方许可加入部署产物。尚未安装前调度器等待，不判为已可用业务系统。

## 持久化与更新

`wp_data` 保存核心安装与配置，`uploads` 单独保存媒体，数据库与 AnythingLLM 各有独立卷。项目主题和业务插件启动时从镜像更新。核心、商业插件更新必须有显式部署步骤，不能假设更新镜像会覆盖已有 wp_data。不要使用 `docker compose -f docker-compos.yaml down -v` 更新生产。

当前调度器为单副本开发基线；生产前补充任务锁、独立健康监测、失败告警和备份恢复脚本。尚无生产配置或密钥，也未操作生产服务器。

## 本地运行

主配置文件名为 `docker-compos.yaml`，需要显式传入 `-f`。

先启动 Docker Desktop，再执行：

```sh
./ops/local.sh config --quiet
./ops/local.sh up -d --build
./ops/local.sh ps
```

网站地址：http://localhost:8080 。AnythingLLM 按需执行 `./ops/local.sh --profile ai up -d anythingllm`，地址为 http://localhost:3001 。默认先启动网站，避免 AI 镜像下载阻塞预览。仅绑定本机回环地址，无需本地 Traefik 或生产域名。WordPress 首次启动进入安装向导；AnythingLLM 首次需要初始化并设置模型。

`ops/local.sh` 自动生成忽略的 `.env.local` 和数据库随机密钥，不覆盖已有值。本地使用 `onestep-local` 项目名及独立卷，不能将本地卷直接当生产数据库。

停止使用 `./ops/local.sh stop`；查看日志使用 `./ops/local.sh logs --tail=100`。生产使用主配置，不叠加 local 文件。

## 本地页面初始化

已有 WordPress 安装后执行以下命令，创建演示页面、启用主题与业务插件并设置导航。脚本只补建缺失页面，不覆盖已编辑正文，并关闭搜索收录（仅用于本地）。

```sh
./ops/local.sh cp ops/seed-local.php scheduler:/tmp/seed-local.php
./ops/local.sh exec -T scheduler wp eval-file /tmp/seed-local.php
```

当前已完成中文首页及服务、地区、关于和联系页面。多语言、询盘处理及 AI 客服尚未完成，不视为可上线成品。首页场景图为 CSS 设计示意，未冒用客户真实案例。

## 询盘开发进度

联系页已接入本地询盘接口 `/wp-json/xdj/v1/inquiry`，支持数据库保存、随机编号、幂等提交、基础限流与校验；后台“网站询盘”仅管理员可查看和更新状态。测试：`python3 tests/inquiry-smoke.py`（会写入明确标记的测试记录）。

当前为项目插件本地存储适配器，尚未替换为技术方案规定的 Gravity Forms entry 适配器，不能直接当作生产实现。未配置商业许可证、地区销售权限、通知任务、Brevo 或 AnythingLLM。通知字段保持 pending，前端不会宣称邮件已发送。原子限流、审计、提交凭证和完整数据生命周期仍待实施。

## 旧站迁移与免费多语言

Polylang 免费版已用于本地语言路由、页面和产品翻译关联。商业插件不再作为实施依赖。迁移结果见 `migration/执行报告.md`、`import-report.json` 和 `media-map.json`。抓取的旧 HTML 保存在忽略目录，产品资料在本地预览中注明待审核。

测试：`python3 tests/multilingual-smoke.py`。图片 CDN 返回 567，原图尚未本地化；德国旧站为英文，德语目录没有冒充翻译。后台案例为待审草稿。当前表单的非中文结果提示仍为英文，尚需完整各语校对。

Polylang 3.9.2 官方发布包已纳入 docker/vendor，Dockerfile 校验 SHA256 后解压到镜像；保留包内许可证与来源记录。新环境仍需执行语言初始化与内容导入脚本。

## 媒体迁移验证（2026-11-13）

已修复 CDN 请求（来源与 Range）、lazy 图片读取，三段原视频和采集图片已落入本地 uploads/legacy。运行 `python3 tests/media-migration.py` 验证本地图片、视频解码、Range 和七语视频页。视频入口：`/zh-hans/videos-zh-hans/`、`/en/videos-en/`。媒体文件需独立备份，Git 中的清单不是文件备份。

## 与 Beacon 共用服务器

独立站使用独立的生产 Compose、GHCR 镜像和发布目录，仅共享现有 Traefik 的 `matrix-network`。部署准备、GitHub 变量、数据迁移与失败恢复见 [云部署与 GitHub Actions](docs/云部署与GitHubActions.md)。本地仍使用 `./ops/local.sh`，云端使用 `docker-compos.production.yaml`。
