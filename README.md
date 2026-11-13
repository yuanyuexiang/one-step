# One Step WordPress

小当家七语独立站：WordPress、免费 Polylang、MySQL、自有主题与业务插件。生产地址 https://ai-onestep.com ，通过 Docker Compose 接入共享服务器已有 Traefik；不修改 Beacon。

## 本地运行

启动 Docker Desktop，在仓库根目录执行：

```sh
./ops/local.sh config --quiet
./ops/local.sh up -d --build
./ops/local.sh ps
```

访问 http://localhost:8080 。包装命令只负责生成忽略的 `.env.local`、随机数据库密钥并选择本地 Compose；也可直接使用等价命令：

```sh
docker compose --env-file .env.local -p onestep-local \
  -f docker-compos.yaml -f docker-compos.local.yaml up -d --build
```

主文件名按项目约定为 `docker-compos.yaml`。本地只绑定回环端口，使用独立卷。查看日志用 `./ops/local.sh logs --tail=100`，停止用 `./ops/local.sh stop`。不要通过 `down -v` 更新有数据的站点。修改主题/插件后需重新构建；容器内改动不会持久保存在源码。

## 初始化与迁移

新数据库先完成 WordPress 安装，再执行 `ops/seed-local.php`、`ops/migration/languages.php` 和 `site-pages.php` 建立基础页面与七种语言。旧站导入工具及媒体清单位于 `ops/migration/`、`migration/`；原图和视频必须单独传入 uploads，Git 清单不能替代媒体备份。测试夹具只用于隔离 CI，不得导入客户站。

已有旧站迁移数据后，备份并运行版本化补齐：

```sh
./ops/local.sh exec -T scheduler wp eval-file /var/www/html/wp-content/plugins/xdj-business/migrate.php
```

该命令按版本幂等，补齐图库、分类、七语页面、德语产品、活动、合作展示和售后手册。生产工作流在备份后显式执行，不重新导入整个数据库或覆盖新增询盘。当前范围和内容限制见 [迁移补齐实施与验收](docs/迁移补齐实施与验收.md)。

## 功能与外部配置

七语路由为 `zh-hans`、`zh-hant`、`en`、`fr`、`de`、`it`、`es`。保留旧 Logo、320 个源图片文件、三段原视频和产品图库，提供轮播、相册、搜索、产品咨询及后台处理。

资料助手默认检索本站，返回真实资料链接，不需要模型密钥。用户尚无模型和 SMTP 配置：生成式回答和邮件投递暂不启用，咨询仍保存到数据库。可选 AnythingLLM 通过 `./ops/local.sh --profile ai up -d anythingllm` 启动；需自行初始化模型、配置 API 与工作区。SMTP 环境变量见 `.env.example`，配置后在后台“网站服务设置”选择通知收件邮箱并验收实际投递。

原图中文和部分历史资料保留为原文档案；不声称全部图片文字已翻译。当前保留搜索引擎 `noindex`，正式 SEO 开放前应完成业务审核。

## 验证与部署

```sh
python3 tests/inquiry-smoke.py
python3 tests/multilingual-smoke.py
python3 tests/complete-site-smoke.py
python3 tests/media-migration.py
python3 ops/deploy/validate.py
```

前三项会写入带标记的测试询盘，只用于本地/隔离环境。媒体检查需要本地迁移文件和 `ffmpeg`。CI 从空数据库建立夹具，执行迁移两次并验证翻译关系和页面功能。

推送 `master` 触发 GitHub Actions 检查、构建 GHCR digest 镜像、SSH 备份和发布。生产使用 `docker-compos.production.yaml`、`onestep` 项目和独立卷，只共享 `matrix-network`/Traefik。配置、发布状态与失败恢复见 [云部署与 GitHub Actions](docs/云部署与GitHubActions.md)。任何密钥、`.env.local`、询盘数据和原始抓取文件均不得提交。
