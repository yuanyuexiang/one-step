# 独立站云部署与 GitHub Actions

## 部署边界

独立站与 Beacon 共用服务器和现有 Traefik，Beacon 项目只作为只读参考。本项目不修改 Beacon 文件、不调用其发布网关、不重建 Traefik。

| 项目 | 独立站配置 |
|---|---|
| 域名 | `ai-onestep.com` |
| Compose project | `onestep` |
| 外部入口网络 | `matrix-network` |
| Traefik | `websecure`，证书 resolver `route53` |
| 服务路由 | `onestep`，内部 WordPress 端口 80 |
| 服务器目录 | `/opt/onestep/{config,releases,state,backups}` |
| 数据卷 | `onestep-db-data`、`onestep-wp-data`、`onestep-uploads`、`onestep-anythingllm-data` |

[生产 Compose](../docker-compos.production.yaml) 独立使用，不叠加本地文件，不映射任何宿主机端口。MySQL、调度器和 AI 不接入公共入口网络。AnythingLLM 仍需单独初始化，目前默认不启动。

## 首次准备

以下为待执行步骤，本次未连接服务器或配置 GitHub Secrets：

1. 管理员创建上述目录，以及独立部署用户；目录由该用户管理，配置目录限制访问。部署账号需要 Docker 权限，该权限等同主机高权限，应使用独立密钥，不复用 Beacon 的 CI 密钥。当前脚本是独立 SSH 部署，尚未实现 Beacon 的受限 sudo 网关。
2. 将 [runtime.env.example](../ops/deploy/runtime.env.example) 配置为 `/opt/onestep/config/runtime.env`。分别创建 `config/secrets/db_password`、`db_root_password`，限制文件权限，不复制本地密码。确认现有 `matrix-network` 和 `route53` 对目标域名可用；DNS 应指向同一服务器。
3. 将当前 WordPress 数据库、核心配置及 uploads 备份并导入独立站卷，使用正式数据库密钥，校验序列化数据中的域名替换。**镜像不包含数据库或迁移媒体，不能只拉镜像就算迁移完成。** 首次手工启动与恢复应使用生产文件、明确的两个镜像 digest 和 `--project-name onestep`。首次发布从服务器 `/opt/onestep/import` 中的数据库、媒体和校验清单恢复站点，恢复时禁用公共路由，不发布空安装向导。
4. GitHub 创建 `production` Environment。首次数据迁移验收前配置 required reviewers，阻止意外自动上线。配置下表变量与密钥；主机指纹人工核验，禁止自动接受未知主机密钥。
5. 核对主机内存、磁盘和备份空间，再启用流水线。AI 启用前单独评估资源并替换其镜像占位 digest。

| 类型 | 名称 | 内容 |
|---|---|---|
| Variable（可选） | `ONESTEP_DEPLOY_HOST` | 默认 `18.175.156.130`，与 Beacon 共用服务器 |
| Variable | `ONESTEP_DEPLOY_USER` | 独立站专用部署用户 |
| Secret | `ONESTEP_DEPLOY_SSH_KEY` | 独立站专用 CI 私钥 |
| Secret（可选） | `ONESTEP_DEPLOY_KNOWN_HOSTS` | 覆盖仓库内已保存的服务器公钥 |

## 流水线与日常发布

[ci-cd.yml](../.github/workflows/ci-cd.yml) 对 PR 执行 Compose 检查、PHP 语法检查和隔离数据库询盘测试。完整多语言和媒体回归依赖本地迁移数据，仍需本地运行，CI 不上传客户数据库。

推送 `master` 在检查通过后构建两个 `linux/amd64` 镜像并推送 GHCR，保留 SHA 标签、digest、SBOM 与 provenance。部署 job 使用 `production` Environment；手动运行只有勾选 `deploy` 才发布。未提交或推送的本地修改不会触发部署。

服务器使用临时 GHCR 登录信息拉取固定 digest；先验证配置、已安装站点并拉取镜像，再获取的发布锁内暂停网站写入、备份 MySQL 和 WordPress 文件。校验备份归档及校验和后启动新版本，检查镜像身份与七种语言的公网 HTTPS，最后启动调度器。发布会短暂停站。

## 失败处理与限制

停止网站之前的检查或拉取失败不会更换业务容器。开始停站后失败会保留 `/opt/onestep/state/pending`，阻止继续覆盖；管理员根据当前容器与备份核实恢复步骤，不能直接删除 pending 重试。`state/current`、`previous` 记录成功版本目录。

当前不自动回滚数据库或插件数据结构；需要确认兼容性后恢复上一版本代码，必要时在维护窗口恢复配套数据库和文件。备份仅覆盖独立站，不触碰 Beacon，默认保留全部备份；异机备份、空间告警、保留策略和恢复演练仍须上线前落实。AI 启用后的存储备份尚未加入工作流发布步骤。

## 本地验证

```sh
python3 ops/deploy/validate.py
actionlint .github/workflows/ci-cd.yml
./ops/local.sh config --quiet
```

上述检查不连接云服务器。服务器初始化、GitHub 实际流水线与首次恢复尚未实测，不代表已经上线。

服务器公钥已从本机既有 `~/.ssh/known_hosts` 复制到 `ops/deploy/known_hosts`，没有重新扫描或自动接受新密钥。主机公钥不是登录私钥，可以版本管理；服务器换机或密钥轮换时须另行核验后更新。GitHub Secret 无法读取明文，因此未复制 Beacon 的 CI 私钥。

## 一次性初始化 Actions 凭据

Beacon 的 CI 私钥绑定强制命令，只能调用 Beacon 网关，不能直接供独立站执行 SSH/SCP。独立站新增初始化工具，先检查 GitHub 登录和目标仓库权限，再创建独立站专用密钥、服务器账号与目录，并仅写入 `yuanyuexiang/one-step` 的两个 Secrets、两个 Variables 和 `production` Environment。

```sh
gh auth login
python3 ops/deploy/configure-actions.py --admin-key /absolute/path/to/server-admin.pem
```

管理密钥仅用于服务器初始化，不上传 GitHub；生成的独立站私钥保存在忽略目录 `secrets/`。新部署账号具有 Docker 权限，相当于主机管理权限；这与 Beacon 的受限发布网关实现不同。脚本不修改 Beacon 文件、账号或网关，不发布网站或导入数据；仅在缺失时生成独立站运行配置和数据库密码。初始化成功后仍需完成上文的 runtime.env、独立站数据库和媒体恢复步骤。脚本不会自动更改既有 Environment 审批规则。

### 2026-11-13 凭据初始化记录

已通过本机 GitHub CLI 为 `one-step` 配置 `ONESTEP_DEPLOY_SSH_KEY`、`ONESTEP_DEPLOY_KNOWN_HOSTS` 及部署主机、账号 Variables，创建 `production` Environment。服务器独立账号 `onestep-deploy` 已实测 SSH、Docker 和 `matrix-network` 访问成功。未修改 Beacon 文件、账号或发布网关。

初始化工具现在会补建独立站 `runtime.env` 和两个随机数据库密码，不覆盖已有配置。数据库密码文件采用部署账号所有、GID 33、0640，以供 WordPress/WP-CLI 读取；不向日志输出密码。AnythingLLM 默认关闭，其镜像占位值启用前必须替换。

`production` Environment 当前没有审批规则；master 推送将按工作流进入自动发布。首次发布必须先准备完整迁移包；没有迁移包时发布会在停站前拒绝继续。GitHub 实际流水线、首次数据恢复及上线仍未验收；不能据凭据配置成功宣称已上线。

### 自动触发分支修正

仓库默认分支为 `master`。原工作流误用 `main`，导致提交已推送但没有产生运行记录；现已统一 push、pull_request 和构建／部署条件为 `master`。修复需提交并推送后才在 GitHub 生效。

### CI 简化与密码文件权限修正

日常 CI 直接执行 `docker compose`，不再调用 `ops/local.sh` 或单独的 `release.sh`。生产 SSH 发布命令集中在工作流中，仍保留备份、互斥锁和失败状态。`configure-actions.py` 仅供一次性初始化账号及凭据，不参与每次发布。

GitHub Linux runner 会保留文件挂载的宿主机权限。原密码文件为 runner 所有、0600，而 WP-CLI 使用 UID 33，导致读取失败。CI 和本地开发将数据库密码文件设为 0644，父目录保持 0700；只向需要它的容器挂载。数据库 root 密码仍为 0600。生产配置维持 GID 33、0640，不改成开发权限。CI 安装改为 `docker compose exec -T scheduler wp core install`，先检查密码可读性，避免 `run` 重建依赖容器。

### 首次发布顺序修正与迁移包

原工作流在空数据卷上直接调用 WP-CLI，尚未启动 WordPress 写入核心文件，导致 `This does not seem to be a WordPress installation`。修复后，无成功发布记录时先校验 `/opt/onestep/import/{database.sql,uploads.tar.gz,languages.tar.gz,SHA256SUMS}`，关闭独立站 Traefik 路由启动 MySQL 和 WordPress，确认数据库没有既有表，再导入正文、翻译关联、媒体和 WordPress 中文语言包，替换本地 URL，刷新路由，然后启用正式服务并验证。

导入仅限空数据库，已有数据但没有成功发布记录时停止并要求核对，禁止静默覆盖。失败仍保留 pending；已有成功发布记录的后续更新沿用备份和升级流程，不重新导入。迁移包存放于忽略的 `backups/first-deploy/` 并通过 SSH 传到服务器，不提交 Git、不上传 Actions artifact，包含原网站后台账号数据，应限制访问。不会复制本地 wp-config.php 或本地数据库密钥。

首次部署迁移包现已上传至服务器私有目录，`database.sql`、`uploads.tar.gz`、`languages.tar.gz` 均通过服务器端 SHA256 校验。本地空卷已验证 WordPress 核心初始化和数据库导入；完整恢复环境已验证 115 个已发布产品、七种语言导航及视频页面。云端尚未执行修复后的工作流，当前不能声明站点上线成功。
