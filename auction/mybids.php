<?php require_once 'utilities.php'; close_expired_auctions();include_once 'header.php'; // 先结算已到期拍卖 include_once 'header.php'; ?>
<div class="container">
    <h2 class="my-3">My bids</h2>
    <?php
    // This page is for showing a user the auctions they've bid on.
    // It will be pretty similar to browse.php, except there is no search bar.
    // This can be started after browse.php is working with a database.
    // Feel free to extract out useful functions from browse.php and put them in
    // the shared "utilities.php" where they can be shared by multiple files.

    // TODO: Check user's credentials (cookie/session).
    if (!is_logged_in()) {
        echo '<p>You must be logged in to view your bids.</p>';
        include_once 'footer.php';
        exit();
    }

    $user_id = current_user_id();

    // TODO: Perform a query to pull up the auctions they've bidded on.
    /* 说明：
     * - a: auctions
     * - i: items
     * - b: bids
     * - p: payments (新增用于检查支付状态)
     *
     * 我们要得到：
     * - 每个拍卖的基本信息（title/description/end_date/status/winner_id）
     * - 这场拍卖的最高出价（current_price）
     * - 当前用户在该拍卖中的最高出价（my_max_bid）
     * - 出价次数（num_bids）
     * - 是否已支付 (is_paid)
     */
    $sql = "
        SELECT 
            a.auction_id, a.item_id, a.end_date, a.status, a.winner_id, i.title, i.description, 
            COALESCE(MAX(b.bid_amount), a.start_price) AS current_price, 
            COUNT(b.bid_id) AS num_bids, 
            MAX(CASE WHEN b.buyer_id = ? THEN b.bid_amount ELSE NULL END) AS my_max_bid,
            -- ⭐ 修正1: 新增字段，检查是否存在已完成的支付记录
            MAX(CASE WHEN p.status = 'completed' AND p.user_id = ? THEN 1 ELSE 0 END) AS is_paid 
        FROM 
            auctions a 
        JOIN 
            items i ON a.item_id = i.item_id 
        JOIN 
            bids b ON a.auction_id = b.auction_id
        -- ⭐ 修正1: 左联接 payments 表
        LEFT JOIN 
            payments p ON a.auction_id = p.auction_id 
        WHERE 
            b.buyer_id = ? 
        GROUP BY 
            a.auction_id
        ORDER BY 
            a.end_date DESC
    ";
    // ⭐ 修正2: db_query 的参数从 'ii' 变为 'iii'，以匹配新增的 SQL 绑定参数
    $result = db_query($sql, 'iii', [$user_id, $user_id, $user_id]);

    // TODO: Loop through results and print them out as list items.
    if (!$result || $result->num_rows === 0):
    ?>
    <p>You have not placed any bids yet.</p>
    <?php else: ?>
    <ul class="list-group">
        <?php while ($row = $result->fetch_assoc()):
            $item_id = (int)$row['item_id'];
            $title = $row['title'];
            $description = $row['description'];
            $current_price = (float)$row['current_price'];
            $num_bids = (int)$row['num_bids'];
            $end_time = new DateTime($row['end_date']);
            $status = $row['status'];
            $winner_id = $row['winner_id'] !== null ? (int)$row['winner_id'] : null;
            $my_max_bid = $row['my_max_bid'] !== null ? (float)$row['my_max_bid'] : null;
            
            // ⭐ 修正3: 使用 SQL 返回的 is_paid 字段来定义 $paid 变量，消除警告并获取真实状态
            $paid = (int)$row['is_paid'] === 1;

            // 计算拍卖是否结束
            $now = new DateTime();
            $ended = ($now >= $end_time || $status === 'finished' || $status === 'cancelled');

            // 计算“结果”文字
            $result_text = '';
            if (!$ended) {
                // 拍卖还在进行，判断当前是否领先
                if ($my_max_bid !== null && $my_max_bid >= $current_price) {
                    $result_text = 'Currently winning';
                } else {
                    $result_text = 'Outbid';
                }
            } else {
                // 拍卖已结束，根据 winner_id 判断是否获胜
                if ($winner_id !== null && $winner_id === $user_id) {
                    $result_text = 'You won';
                } else {
                    $result_text = 'You lost';
                }
            }
        ?>
        <li class="list-group-item">
            <div class="d-flex justify-content-between">
                <div>
                    <h5>
                        <a href="listing.php?item_id=<?php echo $item_id; ?>">
                            <?php echo htmlspecialchars($title); ?>
                        </a>
                    </h5>
                    <p class="mb-1">
                        <?php
                        // 简单截断描述，避免太长
                        $desc_short = (strlen($description) > 120) ? substr($description, 0, 120) . '...' : $description;
                        echo htmlspecialchars($desc_short);
                        ?>
                    </p>
                    <small>
                        Bids you placed: <?php echo $num_bids; ?> total bids in this auction
                    </small>
                </div>
                <div class="text-right text-nowrap ml-3">
                    <div>Current price: £<?php echo number_format($current_price, 2); ?></div>
                    <?php if ($my_max_bid !== null): ?>
                        <div>Your highest bid: £<?php echo number_format($my_max_bid, 2); ?></div>
                    <?php endif; ?>
                    <div> Ends: <?php echo date_format($end_time, 'j M H:i'); ?> </div>
                    
                    
                    <div>
                      <?php
                          $color = 'text-secondary';  // 默认灰色

                          if ($result_text === 'Outbid') {
                              $color = 'text-danger';  // 红色
                          } elseif ($result_text === 'Currently winning' || $result_text === 'You won') {
                              $color = 'text-success'; // 绿色
                          }
                      ?>
                      <strong class="<?php echo $color; ?>">
                          <?php echo htmlspecialchars($result_text); ?>
                      </strong>
                    </div>

                    <?php if ($ended && $winner_id === $user_id): ?>

                        <?php if (!$paid): ?>
                            <a href="pay.php?auction_id=<?= $row['auction_id'] ?>"
                               class="btn btn-sm btn-success mt-2">
                                Pay now
                            </a>
                        <?php else: ?>
                            <span class="badge bg-success mt-2">Paid</span>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </li>
        <?php endwhile; $result->free(); ?>
    </ul>
    <?php endif; ?>
</div>
<?php include_once("footer.php")?>