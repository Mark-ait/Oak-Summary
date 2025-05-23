<?php
/**
 * Plugin Name: OAK-Summary
 * Plugin URI: https://example.com/deepseek-ai-summary
 * Description: 使用DeepSeek AI为文章生成自动摘要，支持异步加载和短代码调用。
 * Version: 1.0.2
 * Author: Oaklee
 * Author URI: https://lilianhua.com
 * License: GPL-2.0+
 * 确保将此此段添加至 wp_posts:ALTER TABLE `wp_posts` ADD `summary` TEXT NULL DEFAULT NULL AFTER `post_content`;
 */


// 如果直接访问插件文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

class DeepSeek_AISummary {
    // 插件实例
    private static $instance;
    
    // 构造函数
    private function __construct() {
        // 注册钩子
        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_head', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_get_deepseek_summary', [$this, 'ajax_handler']);
        add_action('wp_ajax_nopriv_get_deepseek_summary', [$this, 'ajax_handler']);
        
        // 移除自动内容追加（保留短代码调用）
        // add_filter('the_content', [$this, 'append_summary_to_content']);
        
        // 添加管理菜单
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    // 获取插件实例（单例模式）
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 注册短代码
    public function register_shortcode() {
        add_shortcode('deepseek_summary', [$this, 'render_shortcode']);
    }
    
    // 加载内联脚本和样式
    public function enqueue_scripts() {
        if (has_shortcode(get_post()->post_content, 'deepseek_summary') || is_single()) {
            $ajaxConfig = [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('deepseek_summary_nonce')
            ];
            
            ?>
<style>
.deepseek-ai-summary {
    margin: 2rem 0;
    padding: 1.5rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
    border: 2px solid #e5e5e5;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.summary-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.summary-powered-by-container {
    display: flex;
    align-items: center;
}

.summary-powered-by {
    font-size: 0.75rem;
    color: #6b7280;
    margin-right: 0.5rem;
}

.summary-toggle-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #0d6efd;
    transition: transform 0.3s ease;
    padding: 0.25rem;
    font-size: 1.2rem;
}

.summary-toggle-btn:hover {
    color: #0a58ca;
    transform: scale(1.1);
}

.summary-content {
    font-size: 0.95rem;
    line-height: 1.6;
    color: #374151;
    transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
    overflow: hidden;
}

.hidden {
    max-height: 0;
    opacity: 0;
    display: none;
}

.summary-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 0;
    color: #6b7280;
}

.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}

.summary-error {
    color: #dc3545;
    padding: 1rem 0;
}

.summary-loaded {
    padding: 1rem 0;
    background-color: #282c34;
    color:#fff;
    padding: 1rem;
    border-radius: 4px;
}

</style>
<script>
// DeepSeek AJAX配置
const deepseekAISummary = <?php echo json_encode($ajaxConfig); ?>;

// 存储已加载的摘要内容
const loadedSummaries = {};

// 切换摘要显示/隐藏
function toggleDeepSeekSummary(containerId, postId, button) {
    const container = document.getElementById(containerId);
    const content = container.querySelector('.summary-content');
    
    // 如果内容已加载，直接切换显示状态
    if (loadedSummaries[postId]) {
        content.innerHTML = '<div class="summary-loaded">'+ loadedSummaries[postId] +'</div>';
        content.classList.toggle("hidden");
    } else {
        // 否则先显示加载状态，然后请求摘要
        content.innerHTML = 
            '<div class="summary-loading">' +
            '<div class="spinner-border" role="status">' +
            '<span class="visually-hidden"></span></div>' +
            '<p>正在生成AI摘要，请稍候...</p></div>';
        content.classList.remove("hidden");
        
        // 发送AJAX请求
        fetch(deepseekAISummary.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "action=get_deepseek_summary&post_id=" + postId + "&nonce=" + deepseekAISummary.nonce,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 存储并显示摘要
                loadedSummaries[postId] = data.data;
                content.innerHTML = '<div class="summary-loaded">'+ data.data +'</div>';
            } else {
                content.innerHTML = '<div class="summary-error">' +
                    '<i class="bi bi-exclamation-triangle text-danger"></i> ' +
                    '<p>摘要生成失败: '+ (data.data || "未知错误") +'</p></div>';
            }
        })
        .catch(error => {
            console.error("Error:", error);
            content.innerHTML = '<div class="summary-error">' +
                '<i class="bi bi-exclamation-triangle text-danger"></i> ' +
                '<p>摘要生成失败: 网络错误</p></div>';
        });
    }
    
    // 更新按钮图标
    const icon = button.querySelector("i");
    if (content.classList.contains("hidden")) {
        icon.classList.remove("bi-toggle-on");
        icon.classList.add("bi-toggle-off");
    } else {
        icon.classList.remove("bi-toggle-off");
        icon.classList.add("bi-toggle-on");
    }
}
</script>
            <?php
        }
    }
    
    // 渲染短代码
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
            'title' => 'AI摘要',
            'toggle' => 'true',
            'show_loading' => 'true'
        ], $atts, 'deepseek_summary');
        
        $post_id = intval($atts['post_id']);
        if (!$post_id) {
            return '<div class="deepseek-ai-summary error">无效的文章ID</div>';
        }
        
        $unique_id = 'deepseek-summary-' . $post_id . '-' . uniqid();
        
        $html = '<div class="deepseek-ai-summary" id="' . $unique_id . '">';
        $html .= '<div class="summary-header">';
        $html .= '<h3 style="color:#002fa7;"><i class="iconfont icon-robot" style="font-size:22px !important;"></i> ' . esc_html($atts['title']) . '</h3>';
        
        if ($atts['toggle'] === 'true') {
            $html .= '<div class="summary-powered-by-container">';
            $html .= '<span class="summary-powered-by">由 DeepSeek 提供支持</span>';
            $html .= '<button class="summary-toggle-btn" onclick="toggleDeepSeekSummary(\'' . $unique_id . '\', \'' . $post_id . '\', this)">';
            $html .= '<i class="bi bi-toggle-off"></i>';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; 
        $html .= '<div id="summary-content-' . $post_id . '" class="summary-content hidden">';
        
        if ($atts['show_loading'] === 'true') {
            $html .= '<div class="summary-loading">';
            $html .= '<div class="spinner-border" role="status">';
            $html .= '<span class="visually-hidden"></span>';
            $html .= '</div>';
            $html .= '<p>正在生成AI摘要，请稍候...</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; 
        $html .= '</div>'; 
        
        return $html;
    }
    
    // AJAX请求处理
    public function ajax_handler() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'deepseek_summary_nonce')) {
            wp_send_json_error('无效的请求验证');
            wp_die();
        }
        
        $postId = intval($_POST['post_id']);
        if (!$postId) {
            wp_send_json_error('无效的文章ID');
            wp_die();
        }
        
        global $wpdb;
        
        $summary_column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$wpdb->posts} LIKE 'summary'");
        
        if (!$summary_column_exists) {
            $wpdb->query("ALTER TABLE {$wpdb->posts} ADD COLUMN summary TEXT AFTER post_content");
        }
        
        $summary = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT summary FROM {$wpdb->posts} WHERE ID = %d",
                $postId
            )
        );

        if (empty($summary)) {
            $post = get_post($postId);
            if (!$post) {
                wp_send_json_error('文章不存在');
                wp_die();
            }
            
            $rawContent = $post->post_content;
            $cleanContent = strip_tags($rawContent);
            $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);
            $cleanContent = trim($cleanContent);
            
            $apiConfig = $this->get_api_config();
            
            if (empty($apiConfig['api_key'])) {
                wp_send_json_error('请在设置中配置DeepSeek API密钥');
                wp_die();
            }
            
            if (mb_strlen($cleanContent) > $apiConfig['content_length']) {
                $cleanContent = mb_substr($cleanContent, 0, $apiConfig['content_length']) . '...';
            }
            
            $summary = $this->generate_summary($cleanContent, $apiConfig);
            
            $wpdb->update(
                $wpdb->posts,
                ['summary' => $summary],
                ['ID' => $postId],
                ['%s'],
                ['%d']
            );
        }
        
        wp_send_json_success($summary);
        wp_die();
    }
    
    // 获取API配置
    private function get_api_config() {
        $options = get_option('deepseek_ai_summary_options');
        
        return [
            'api_key' => isset($options['api_key']) ? trim($options['api_key']) : '',
            'model' => isset($options['model']) ? $options['model'] : 'deepseek-chat',
            'temperature' => isset($options['temperature']) ? floatval($options['temperature']) : 0.3,
            'max_tokens' => isset($options['max_tokens']) ? intval($options['max_tokens']) : 300,
            'content_length' => isset($options['content_length']) ? intval($options['content_length']) : 2000,
            'cache_duration' => isset($options['cache_duration']) ? intval($options['cache_duration']) : 0,
        ];
    }
    
    // 生成摘要
    private function generate_summary($content, $config) {
        if (empty($content)) return "文章内容为空，无法生成摘要";
        
        if (empty($config['api_key'])) {
            return "缺少DeepSeek API密钥";
        }
        
        $apiUrl = 'https://api.deepseek.com/v1/chat/completions';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        ];
        
        $data = [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "请为以下文章生成一个简洁的摘要（约50-100字）：\n\n$content"
                ]
            ],
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return "API请求失败 (HTTP $httpCode): $error";
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "API响应解析失败: " . json_last_error_msg();
        }
        
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        } else {
            return "API返回格式异常: " . print_r($result, true);
        }
    }
    
    // 添加管理菜单
    public function add_admin_menu() {
        add_options_page(
            'DeepSeek AI 自动摘要设置',
            'DeepSeek AI 摘要',
            'manage_options',
            'deepseek-ai-summary',
            [$this, 'settings_page']
        );
    }
    
    // 注册设置
    public function register_settings() {
        register_setting('deepseek_ai_summary_group', 'deepseek_ai_summary_options');
        
        add_settings_section(
            'deepseek_ai_summary_section',
            'DeepSeek API 设置',
            [$this, 'section_callback'],
            'deepseek-ai-summary'
        );
        
        add_settings_field(
            'api_key',
            'API 密钥',
            [$this, 'api_key_callback'],
            'deepseek-ai-summary',
            'deepseek_ai_summary_section'
        );
        
        add_settings_field(
            'model',
            '模型名称',
            [$this, 'model_callback'],
            'deepseek-ai-summary',
            'deepseek_ai_summary_section'
        );
        
        add_settings_field(
            'temperature',
            '温度值',
            [$this, 'temperature_callback'],
            'deepseek-ai-summary',
            'deepseek_ai_summary_section'
        );
        
        add_settings_field(
            'max_tokens',
            '最大令牌数',
            [$this, 'max_tokens_callback'],
            'deepseek-ai-summary',
            'deepseek_ai_summary_section'
        );
        
        add_settings_field(
            'content_length',
            '最大内容长度',
            [$this, 'content_length_callback'],
            'deepseek-ai-summary',
            'deepseek_ai_summary_section'
        );
    }
    
    // 设置页面回调
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>DeepSeek AI 自动摘要设置</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('deepseek_ai_summary_group');
                do_settings_sections('deepseek-ai-summary');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    // 各字段回调函数
    public function section_callback() {
        echo '<p>配置DeepSeek AI API连接参数</p>';
    }
    
    public function api_key_callback() {
        $options = get_option('deepseek_ai_summary_options');
        echo '<input type="password" name="deepseek_ai_summary_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">从DeepSeek平台获取的API密钥</p>';
    }
    
    public function model_callback() {
        $options = get_option('deepseek_ai_summary_options');
        echo '<input type="text" name="deepseek_ai_summary_options[model]" value="' . esc_attr($options['model'] ?? 'deepseek-chat') . '" class="regular-text" />';
        echo '<p class="description">要使用的DeepSeek模型名称</p>';
    }
    
    public function temperature_callback() {
        $options = get_option('deepseek_ai_summary_options');
        echo '<input type="number" step="0.1" min="0" max="2" name="deepseek_ai_summary_options[temperature]" value="' . esc_attr($options['temperature'] ?? '0.3') . '" class="small-text" />';
        echo '<p class="description">控制输出的随机性，较低的值会使输出更确定性，较高的值会使输出更随机</p>';
    }
    
    public function max_tokens_callback() {
        $options = get_option('deepseek_ai_summary_options');
        echo '<input type="number" min="100" max="2000" name="deepseek_ai_summary_options[max_tokens]" value="' . esc_attr($options['max_tokens'] ?? '300') . '" class="small-text" />';
        echo '<p class="description">生成摘要的最大token数量</p>';
    }
    
    public function content_length_callback() {
        $options = get_option('deepseek_ai_summary_options');
        echo '<input type="number" min="500" max="10000" name="deepseek_ai_summary_options[content_length]" value="' . esc_attr($options['content_length'] ?? '2000') . '" class="small-text" />';
        echo '<p class="description">发送给API的最大内容长度（字符数）</p>';
    }
}

// 初始化插件
function deepseek_ai_summary_init() {
    DeepSeek_AISummary::get_instance();
}
add_action('plugins_loaded', 'deepseek_ai_summary_init');