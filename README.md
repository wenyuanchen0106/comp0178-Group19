
* **第 1 部分：命名规范**
* **第 2 部分：实体 / 关系 / 键（对齐 ER 图 + 评分表）**
* **第 3 部分：按评分表的分类写出的 *必须实现的功能***。

---

## 1. 命名规范（统一所有人用的名字）

**表名（lower_snake_case、单数）**

```text
roles, users, categories, items, auctions,
bids, payments, reports, recommendations,
favourites, watchlist, autobids
```

**主键 / 外键**

* 主键：`<table>_id`，例如 `user_id`, `auction_id`, `item_id` …
* 外键：沿用目标表主键名，例如：

  * `users.role_id` → `roles.role_id`
  * `items.category_id` → `categories.category_id`
  * `items.seller_id` → `users.user_id`
  * `auctions.item_id` → `items.item_id`
  * `auctions.seller_id` → `users.user_id`
  * `auctions.winner_id` → `users.user_id`
  * `bids.auction_id` → `auctions.auction_id`
  * `bids.buyer_id` → `users.user_id`
  * `payments.user_id` → `users.user_id`
  * `payments.auction_id` → `auctions.auction_id`
  * `reports.user_id` → `users.user_id`
  * `reports.auction_id` → `auctions.auction_id`
  * `reports.item_id` → `items.item_id`
  * `recommendations.user_id` → `users.user_id`
  * `recommendations.item_id` → `items.item_id`
  * `favourites.user_id` → `users.user_id`
  * `favourites.item_id` → `items.item_id`
  * `watchlist.user_id` → `users.user_id`
  * `watchlist.auction_id` → `auctions.auction_id`
  * `autobids.user_id` → `users.user_id`
  * `autobids.auction_id` → `auctions.auction_id`

---

## 2. 实体与关系

### 2.1 用户与角色

* `roles(role_id PK, role_name UNIQUE)`
* `users(user_id PK, name, email UNIQUE, password_hash, role_id FK)`

关系：

* 一个 **role** 对多个 **user**：`users.role_id`
* 用于：注册、登录、权限控制（Core 1）。

---

### 2.2 类别、物品与卖家

* `categories(category_id PK, category_name UNIQUE)`
* `items(item_id PK, title, description, category_id FK, seller_id FK)`

关系：

* 一类 `categories` 下有多件 `items`；
* 一个卖家 `users` 拥有多件 `items`（`items.seller_id`）。
* 用于：物品建模、搜索、分类浏览（Core 2, Core 3）。

---

### 2.3 拍卖

* `auctions(auction_id PK, item_id FK, seller_id FK,
            start_price, reserve_price,
            start_date, end_date,
            winner_id FK NULL, status)`

关系：

* 一个 `items` 对多次 `auctions`（允许同一物品多次拍卖）；
* 一个卖家 `users` 对多个 `auctions`；
* `winner_id` 指向中标买家（Core 4）。

---

### 2.4 出价（竞价）

* `bids(bid_id PK, auction_id FK, buyer_id FK,
       bid_amount, bid_time)`

关系：

* 多对多：`users` 与 `auctions` 通过 `bids` 关联；
* 用于：显示出价历史、计算最高出价、判定赢家（Core 4）。

---

### 2.5 自动出价 & 关注

* `autobids(autobid_id PK, user_id FK, auction_id FK,
            max_amount, step)`
* `watchlist(user_id FK, auction_id FK, PK(user_id, auction_id))`

关系：

* 用户对多个拍卖设置自动出价 / 关注（Extra, E1–E4 / E5）。

---

### 2.6 收藏（Favourite）

* `favourites(user_id FK, item_id FK, PK(user_id, item_id))`

关系：

* 用户收藏多件物品，用于额外推荐或个人中心展示（Extra）。

---

### 2.7 支付

* `payments(payment_id PK, user_id FK, auction_id FK,
            amount, payment_method, status, datetime)`

关系：

* 中标买家支付某个拍卖；
* 用于：成交后记录支付（Extra, E1–E4）。

---

### 2.8 举报

* `reports(report_id PK, user_id FK, auction_id FK NULL,
          item_id FK NULL, description, status, created_at)`

关系：

* 用户对拍卖/物品发起举报；
* 用于：管理员审核（Extra, E1–E4）。

---

### 2.9 推荐

* `recommendations(recommendation_id PK, user_id FK,
                   item_id FK, reason, score)`

关系：

* 存储对某个用户的推荐条目；
* 用于：协同过滤推荐页面（E6）。

---

## 3. 按评分表列出“**明确要求的功能**”（Required functionality）

下面完全对照 design brief 中的 1–4 + E1–E4 + E5 + E6，逐条写出**需要做到什么**。

---

### 3.1 Design 部分（占 20%）

虽然不是“功能”，但是必须提交的设计成果：

1. **ER diagram**

   * Required functionality（文档）：

     * 提供完整 ER 图，包含上面所有实体、关系、键和基数，并写明假设（例如“一件物品可以被多次拍卖”等）。

2. **Schema & 3NF analysis**

   * 根据ER图以及这个文件来写，已经总结了一部分：

     * 给出所有表的 schema（字段名、类型、PK/FK/UNIQUE）；
     * 对主要表（至少：`users, roles, items, categories, auctions, bids`）做 3NF 证明；
     * 说明如何从 ER 图系统性映射到这些表。

3. **Query list**

   * Required functionality：

     * 列出支持各个功能的主要 SQL 语句，并按“文件或功能模块”分组（例如 `listing.php`: 查询拍卖详情+出价历史）。

---

### 3.2 Core 1 – 用户注册与角色（10%）

> “Users can register with the system and create accounts. Users have roles of seller or buyer with different privileges.”

**涉及表：** `users`, `roles`

**Required functionality:**

1. **注册账号**

   * 用户能通过表单创建账户：填写姓名、email、密码、角色（buyer/seller）；
   * 系统在 `users` 中插入一行，并关联 `role_id`；
   * email 唯一检查，密码以 hash 形式存储。

2. **登录 / 登出**

   * 用户能用 email+密码登录；
   * 登录成功后在 session 中保存 `user_id` 和 `role_id`；
   * 用户可以登出，session 被清除。

3. **基于角色的权限**

   * 至少要做到：

     * seller 才能访问“创建拍卖 / 我的拍卖”；
     * buyer 才能出价；
   * 若有人未登录或角色不符访问这些页面，系统要阻止并给出提示。

---

### 3.3 Core 2 – 卖家创建拍卖（10%）

> “Sellers can create auctions… setting item description, categorisation, starting price, reserve price and end date.”

**涉及表：** `items`, `categories`, `auctions`, `users`

**Required functionality:**

1. **创建物品 + 选择分类**

   * 卖家可以录入 item 信息：`title`, `description`，并选择 `category_id`；
   * 系统在 `items` 中创建记录，`seller_id = 当前用户`。

2. **创建拍卖**

   * 对上述 item 或现有 item，卖家可以设定：

     * `start_price`
     * `reserve_price`
     * `start_date`, `end_date`
   * 系统在 `auctions` 表中插入记录，关联 `item_id` 和 `seller_id`；
   * 对上述字段进行基本校验（价格非负，时间合法）。

3. **查看“我的拍卖”**

   * 卖家可以查看由自己创建的所有拍卖，区分进行中/已结束；
   * 对应查询 `auctions` + `items`，过滤 `seller_id = 当前用户`。

---

### 3.4 Core 3 – 搜索与浏览（15%）

> “Buyers can search… and can browse and visually re-arrange listings of items within categories.”

**涉及表：** `auctions`, `items`, `categories`（有时还会 join `bids` 得到当前价）

**Required functionality:**

1. **搜索拍卖**

   * 买家可以按关键词（item title/description）搜索正在进行的拍卖；
   * 可选 category filter，例如只看 “Electronics”。

2. **浏览某个类别的拍卖**

   * 用户可以选择一个 category，系统列出该分类下所有 active auctions（join `items.category_id`）。

3. **列表排序（“re-arrange listings”）**

   * 至少提供 **两个** 排序方式，例如：

     * 按 `end_date` 升序（快结束的在前）；
     * 按当前最高出价 / 起拍价排序；
   * 通过 URL 参数 / 下拉框控制 SQL 的 `ORDER BY`。

---

### 3.5 Core 4 – 出价、管理拍卖、通知结果（15%）

> “Buyers can bid for items and see the bids other users make as they are received… manage the auction until the set end time and award the item to the highest bidder… confirm to both the winner and seller of an auction its outcome.”

**涉及表：** `bids`, `auctions`, `items`, `users`（可能还有 `payments` 作为扩展）

**Required functionality:**

1. **查看拍卖详情 + 出价记录**

   * `listing.php` 查询该 `auction`：

     * join `auctions` + `items` + `users`(seller)；
     * 以及该拍卖所有 `bids`，按 `bid_time` 或 `bid_amount` 排序；
   * 页面上显示当前最高出价。

2. **提交新出价**

   * 登录买家在拍卖未结束时可以输入 `bid_amount`；
   * 系统检查：

     * 拍卖 `status` 为 active 且当前时间 < `end_date`；
     * `bid_amount` 大于当前最高出价 & 起拍价；
   * 若通过，插入到 `bids` 表；失败则给出错误信息。

3. **管理拍卖生命周期**

   * 当达到 `end_date`（或模拟触发）时，系统：

     * 查询该拍卖最高 `bid_amount`；
     * 将其 `buyer_id` 写入 `auctions.winner_id`；
     * 更新 `status = 'finished'`。

4. **结果通知（至少在界面上）**

   * 买家：在 `mybids` 页面可以看到自己是否赢得某些拍卖（比较 `winner_id` = 当前用户）；
   * 卖家：在 `mylistings` 页面看到每个拍卖的赢家和成交价；
   * 这些都通过 join `auctions` + `bids` + `users` 实现。
   * （如果实现 email 通知，可以算作 Extra / E5 加分。）

---

### 3.6 Extra – E1–E4（最多 20 分）

> “Extra functionality related to core features requiring usage of a database.”

这里不限定具体形式，但你的 ER 图已经暗示了几个方向：`payments`, `reports`, `autobids`, `favourites`。

**可以选择的 Required functionality（任选若干）：**

1. **支付记录（payments）**

   * 中标后允许买家“付款”，系统在 `payments` 中插入记录；
   * 卖家可以查看收入/已支付订单；
   * 涉及并发使用：`payments` + `auctions` + `users`.

2. **举报系统（reports）**

   * 用户可对拍卖或物品提交举报，写入 `reports`；
   * 管理员可查看 open reports，更改 `status`；
   * 涉及：`reports` + `auctions` + `items` + `users`.

3. **自动出价（autobids）**

   * 用户可以为某拍卖设定 `max_amount` 和 `step`；
   * 当其他人出价时，系统检查 `autobids`，自动帮该用户加价直到上限；
   * 涉及：`autobids` + `bids` + `auctions`.

4. **收藏物品（favourites）**

   * 用户可以收藏物品/拍卖；
   * 在 “My favourites” 页面列出；
   * 涉及：`favourites` + `items` + `categories`.

> 这些都必须“实打实用到数据库”，仅前端开关不算分。

---

### 3.7 Extra – E5：Watch auctions & notifications（5 分）

> “Buyers can watch auctions on items and receive emailed updates on bids… including notifications when they are outbid.”

**涉及表：** `watchlist`, `bids`, `auctions`, `users`

**Required functionality:**

1. **添加 / 取消关注**

   * 在拍卖详情页有按钮：

     * 点击后向 `watchlist` 插入/删除 (`user_id`, `auction_id`)。

2. **查看我的关注列表**

   * 页面显示当前用户 watch 的所有 active auctions，使用 join `watchlist` + `auctions` + `items`。

3. **被超过出价的通知（至少一种方式）**

   * 最简单做法：

     * 在 `mybids` 或 `watchlist` 页面上标出“你已被 outbid”；
     * 通过比较当前最高 `bid_amount` 与用户自己最高出价。
   * 如果实现真正的 email（调用 PHP `mail()`），属于更高质量实现。

---

### 3.8 Extra – E6：Collaborative filtering recommendation（5 分）

> “Buyers can receive recommendations… based on collaborative filtering（其他也投过你投过的商品的人正在投什么）。”

**涉及表：** `recommendations`, `bids`, `items`, `users`（可能还用到 `categories`）

**Required functionality:**

1. **计算推荐（哪怕是简化版协同过滤）**

   * 逻辑例子：

     * 找出“和你投过同一 auction 的其他用户”；
     * 看他们目前出价的其它 auction / item；
     * 将这些 item 写入 `recommendations`（带上 `reason` 和 `score`）。

2. **展示推荐**

   * 在首页或 `recommendations.php` 中，列出当前用户的推荐 item 列表；
   * 使用 join `recommendations` + `items` (+ `categories`) 展示标题、分类、当前拍卖信息。

3. **推荐更新机制（简单即可）**

   * 可以在用户访问某个页面时重新计算一次；
   * 或提供一个“刷新推荐”按钮，调用后台脚本更新 `recommendations` 表。

---

### 3.9 小结：并发使用哪些表、如何保持一致命名

* **Core 1**：`users` + `roles`
* **Core 2**：`users` + `items` + `categories` + `auctions`
* **Core 3**：`auctions` + `items` + `categories` (+ `bids` for当前价)
* **Core 4**：`auctions` + `bids` + `users` (+ `items`)
* **E1–E4**：

  * 支付：`payments` + `auctions` + `users`
  * 举报：`reports` + `auctions` + `items` + `users`
  * 自动出价：`autobids` + `bids` + `auctions` + `users`
  * 收藏：`favourites` + `items` + `users`
* **E5**：`watchlist` + `bids` + `auctions` + `items` + `users`
* **E6**：`recommendations` + `bids` + `items` + `users`


