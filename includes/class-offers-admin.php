<?php
class Offers_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_save_offer', [$this, 'handle_form']);
        add_action('admin_post_delete_offer', [$this, 'handle_delete']);
    }

    public function add_menu() {
        add_menu_page(
            'Offers Manager', 'Offers', 'manage_options',
            'wp-offers', [$this, 'render_page'], 'dashicons-tag'
        );
    }

    public function handle_form() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('save_offer');
        Offers_DB::insert_offer($_POST);
        wp_redirect(admin_url('admin.php?page=wp-offers&saved=1'));
        exit;
    }

    public function handle_delete() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('delete_offer');
        Offers_DB::delete_offer($_POST['offer_id']);
        wp_redirect(admin_url('admin.php?page=wp-offers&deleted=1'));
        exit;
    }

    public function render_page() {
        global $wpdb;
        $today   = date('Y-m-d');
        $all     = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}offers ORDER BY created_at DESC");
        $api_key = get_option('wp_offers_api_key');
        $total   = count($all);
        $active  = count(array_filter($all, fn($o) => $o->status === 'active' && $o->expiry_date >= $today));
        $inactive = $total - $active;
        $filter  = isset($_GET['filter']) ? $_GET['filter'] : 'all';

        if ($filter === 'active') {
            $offers = array_filter($all, fn($o) => $o->status === 'active' && $o->expiry_date >= $today);
        } elseif ($filter === 'inactive') {
            $offers = array_filter($all, fn($o) => $o->status === 'inactive' || $o->expiry_date < $today);
        } else {
            $offers = $all;
        }
        ?>
        <style>
            
            #wpo { padding: 24px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f0f2f5; min-height: 100vh; }

            /* TOP BAR */
            .wpo-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
            .wpo-topbar h1 { font-size: 20px; font-weight: 700; color: #0f172a; letter-spacing: -0.3px; }
            .wpo-version { font-size: 11px; background: #e0e7ff; color: #3730a3; border-radius: 99px; padding: 3px 10px; font-weight: 600; }

            /* NOTICE */
            .wpo-notice-ok { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 10px 16px; margin-bottom: 20px; color: #15803d; font-size: 13px; font-weight: 600; }
            .wpo-notice-del { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 10px 16px; margin-bottom: 20px; color: #dc2626; font-size: 13px; font-weight: 600; }

            /* API BOX */
            .wpo-api { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
            .wpo-api-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; flex-shrink: 0; }
            .wpo-api-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
            .wpo-api-key { font-family: monospace; font-size: 13px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 12px; color: #0f172a; font-weight: 600; }
            .wpo-api-url { font-size: 12px; color: #94a3b8; margin-left: auto; }

            /* STATS */
            .wpo-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
            .wpo-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; position: relative; overflow: hidden; }
            .wpo-stat::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
            .wpo-stat-total::before { background: #6366f1; }
            .wpo-stat-active::before { background: #22c55e; }
            .wpo-stat-inactive::before { background: #f43f5e; }
            .wpo-stat .num { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 4px; letter-spacing: -1px; }
            .wpo-stat-total .num { color: #6366f1; }
            .wpo-stat-active .num { color: #22c55e; }
            .wpo-stat-inactive .num { color: #f43f5e; }
            .wpo-stat .lbl { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

            /* FORM */
            .wpo-form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; margin-bottom: 20px; }
            .wpo-card-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
            .wpo-card-title span { background: #f1f5f9; border-radius: 6px; padding: 3px 10px; font-size: 11px; color: #64748b; font-weight: 500; }
            .wpo-form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
            .wpo-fg { display: flex; flex-direction: column; gap: 5px; }
            .wpo-fg.full { grid-column: 1 / -1; }
            .wpo-fg label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
            .wpo-fg input,
            .wpo-fg select,
            .wpo-fg textarea { padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; color: #0f172a; background: #f8fafc; width: 100%; font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s; }
            .wpo-fg input:focus,
            .wpo-fg select:focus,
            .wpo-fg textarea:focus { outline: none; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px #6366f120; }
            .wpo-fg textarea { resize: vertical; min-height: 75px; }
            .wpo-form-footer { margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
            .wpo-btn-add { background: #6366f1; color: #fff; border: none; border-radius: 8px; padding: 10px 22px; font-size: 13px; font-weight: 700; cursor: pointer; letter-spacing: 0.02em; transition: background 0.15s; }
            .wpo-btn-add:hover { background: #4f46e5; }

            /* TABLE CARD */
            .wpo-table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
            .wpo-table-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px; }
            .wpo-table-title { font-size: 14px; font-weight: 700; color: #0f172a; }
            .wpo-count { background: #f1f5f9; color: #64748b; font-size: 12px; font-weight: 600; border-radius: 99px; padding: 2px 10px; margin-left: 8px; }

            /* FILTER BUTTONS */
            .wpo-filters { display: flex; gap: 6px; }
            .wpo-fbtn { padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; text-decoration: none; display: inline-block; transition: all 0.15s; }
            .wpo-fbtn:hover { background: #f1f5f9; color: #0f172a; }
            .wpo-fbtn-all.wpo-on { background: #6366f1; color: #fff; border-color: #6366f1; }
            .wpo-fbtn-active.wpo-on { background: #22c55e; color: #fff; border-color: #22c55e; }
            .wpo-fbtn-inactive.wpo-on { background: #f43f5e; color: #fff; border-color: #f43f5e; }

            /* TABLE */
            .wpo-table-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .wpo-table-card thead tr { background: #f8fafc; }
            .wpo-table-card th { text-align: left; padding: 10px 16px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; border-bottom: 1px solid #f1f5f9; }
            .wpo-table-card td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; color: #0f172a; vertical-align: middle; }
            .wpo-table-card tr:last-child td { border-bottom: none; }
            .wpo-table-card tr:hover td { background: #fafbff; }

            /* BADGES */
            .wpo-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.04em; }
            .wpo-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
            .wpo-b-active { background: #dcfce7; color: #15803d; }
            .wpo-b-active::before { background: #22c55e; }
            .wpo-b-inactive { background: #fee2e2; color: #dc2626; }
            .wpo-b-inactive::before { background: #f43f5e; }
            .wpo-b-expired { background: #fef3c7; color: #d97706; }
            .wpo-b-expired::before { background: #f59e0b; }

            /* CODE CHIP */
            .wpo-code { font-family: monospace; font-size: 11px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 5px; padding: 3px 8px; font-weight: 700; letter-spacing: 0.08em; }

            /* DELETE BTN */
            .wpo-del { background: #fff; color: #f43f5e; border: 1px solid #fecdd3; border-radius: 7px; padding: 6px 12px; font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.15s; }
            .wpo-del:hover { background: #f43f5e; color: #fff; border-color: #f43f5e; }

            .wpo-empty { text-align: center; padding: 3rem; color: #94a3b8; font-size: 14px; }
            .wpo-expired-date { color: #f43f5e; }
            .wpo-id { color: #94a3b8; font-size: 12px; }
            .wpo-desc { font-size: 11px; color: #94a3b8; margin-top: 2px; }
        </style>

        <div id="wpo">
            <div class="wpo-topbar">
                <h1>🏷️ Offers Manager</h1>
                <span class="wpo-version">v1.0.0</span>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="wpo-notice-ok">✅ Offer added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="wpo-notice-del"> Offer deleted successfully!</div>
            <?php endif; ?>

            <!-- API BOX -->
            <div class="wpo-api">
                <div class="wpo-api-dot"></div>
                <span class="wpo-api-label">API Key</span>
                <span class="wpo-api-key"><?= esc_html($api_key) ?></span>
                <span class="wpo-api-url">GET /wp-json/offers/v1/active &middot; X-API-Key header required</span>
            </div>

            <!-- STATS -->
            <div class="wpo-stats">
                <div class="wpo-stat wpo-stat-total">
                    <div class="num"><?= $total ?></div>
                    <div class="lbl">Total Offers</div>
                </div>
                <div class="wpo-stat wpo-stat-active">
                    <div class="num"><?= $active ?></div>
                    <div class="lbl">Active</div>
                </div>
                <div class="wpo-stat wpo-stat-inactive">
                    <div class="num"><?= $inactive ?></div>
                    <div class="lbl">Inactive / Expired</div>
                </div>
            </div>

            <!-- FORM -->
            <div class="wpo-form-card">
                <div class="wpo-card-title">Add New Offer <span>* required fields</span></div>
                <form method="POST" action="<?= admin_url('admin-post.php') ?>">
                    <input type="hidden" name="action" value="save_offer">
                    <?php wp_nonce_field('save_offer'); ?>
                    <div class="wpo-form-grid">
                        <div class="wpo-fg">
                            <label>Title *</label>
                            <input name="title" required placeholder="e.g. Summer Sale">
                        </div>
                        <div class="wpo-fg">
                            <label>Discount %</label>
                            <input name="discount" placeholder="e.g. 20% off">
                        </div>
                        <div class="wpo-fg">
                            <label>Discount Code</label>
                            <input name="discount_code" placeholder="e.g. MARCH50">
                        </div>
                        <div class="wpo-fg">
                            <label>Status</label>
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="wpo-fg">
                            <label>Expiry Date *</label>
                            <input type="date" name="expiry_date" required>
                        </div>
                        <div class="wpo-fg full">
                            <label>Description</label>
                            <textarea name="description" placeholder="Describe the offer details..."></textarea>
                        </div>
                    </div>
                    <div class="wpo-form-footer">
                        <button type="submit" class="wpo-btn-add">+ Add Offer</button>
                    </div>
                </form>
            </div>

            <!-- TABLE -->
            <div class="wpo-table-card">
                <div class="wpo-table-header">
                    <div class="wpo-table-title">
                        <?php
                        if ($filter === 'active') echo 'Active Offers';
                        elseif ($filter === 'inactive') echo 'Inactive / Expired Offers';
                        else echo 'All Offers';
                        ?>
                        <span class="wpo-count"><?= count($offers) ?></span>
                    </div>
                    <div class="wpo-filters">
                        <a href="?page=wp-offers&filter=active" class="wpo-fbtn wpo-fbtn-active <?= $filter === 'active' ? 'wpo-on' : '' ?>"> Active</a>
                        <a href="?page=wp-offers&filter=all"    class="wpo-fbtn wpo-fbtn-all    <?= $filter === 'all'    ? 'wpo-on' : '' ?>"> All</a>
                        <a href="?page=wp-offers&filter=inactive" class="wpo-fbtn wpo-fbtn-inactive <?= $filter === 'inactive' ? 'wpo-on' : '' ?>"> Inactive</a>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Discount</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($offers)): ?>
                            <tr><td colspan="7" class="wpo-empty">No offers found for this filter.</td></tr>
                        <?php else: ?>
                            <?php foreach ($offers as $o):
                                $is_expired = $o->expiry_date < $today;
                                if ($o->status === 'inactive') {
                                    $badge = 'wpo-b-inactive'; $label = 'Inactive';
                                } elseif ($is_expired) {
                                    $badge = 'wpo-b-expired'; $label = 'Expired';
                                } else {
                                    $badge = 'wpo-b-active'; $label = 'Active';
                                }
                            ?>
                            <tr>
                                <td class="wpo-id">#<?= $o->id ?></td>
                                <td>
                                    <strong><?= esc_html($o->title) ?></strong>
                                    <?php if ($o->description): ?>
                                        <div class="wpo-desc"><?= esc_html($o->description) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc_html($o->discount) ?></td>
                                <td>
                                    <?php if ($o->discount_code): ?>
                                        <span class="wpo-code"><?= esc_html(strtoupper($o->discount_code)) ?></span>
                                    <?php else: ?>
                                        <span style="color:#e2e8f0;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wpo-badge <?= $badge ?>"><?= $label ?></span></td>
                                <td class="<?= $is_expired ? 'wpo-expired-date' : '' ?>"><?= $o->expiry_date ?></td>
                                <td>
                                    <form method="POST" action="<?= admin_url('admin-post.php') ?>" onsubmit="return confirm('Delete this offer?')">
                                        <input type="hidden" name="action" value="delete_offer">
                                        <input type="hidden" name="offer_id" value="<?= $o->id ?>">
                                        <?php wp_nonce_field('delete_offer'); ?>
                                        <button type="submit" class="wpo-del"> Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
new Offers_Admin();