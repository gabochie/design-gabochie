<?php
/**
 * Footer template
 * @package Gabochie_Design
 */
$socials = gabochie_get_social_links();
$phone = get_theme_mod('company_phone', '+233 26 824 8970');
$email = get_theme_mod('company_email', 'admin@gabochiedesign.com');
$address = get_theme_mod('company_address', "P.O.BOX DM SK 2125\nSakumono, Tema, Ghana");
$whatsapp = get_theme_mod('whatsapp_number', '233268248970');
?>

<!-- AI CHAT WIDGET -->
<?php if (get_theme_mod('ai_chat_enabled', '1') === '1') : ?>
<div id="gd-ai-chat" class="gd-chat-widget">
    <button class="gd-chat-toggle" id="gd-chat-toggle" aria-label="Chat with AI Assistant">
        <svg class="gd-chat-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        <svg class="gd-chat-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        <span class="gd-chat-badge">1</span>
    </button>
    <div class="gd-chat-window" id="gd-chat-window">
        <div class="gd-chat-header">
            <div class="gd-chat-header-info">
                <div class="gd-chat-avatar">
                    <svg viewBox="0 0 52 52" fill="none"><polygon points="26,3 49,15 49,37 26,49 3,37 3,15" fill="none" stroke="rgba(201,169,110,0.8)" stroke-width="1.5"/><text x="26" y="30" text-anchor="middle" font-family="serif" font-size="14" fill="#FAF7F2" font-weight="700">GD</text></svg>
                </div>
                <div>
                    <div class="gd-chat-name">GD Assistant</div>
                    <div class="gd-chat-status"><span class="gd-status-dot"></span>AI Architecture Guide</div>
                </div>
            </div>
            <button class="gd-chat-close-btn" id="gd-chat-close" aria-label="Close chat">&times;</button>
        </div>
        <div class="gd-chat-messages" id="gd-chat-messages"></div>
        <div class="gd-chat-suggestions" id="gd-chat-suggestions">
            <button class="gd-suggestion" data-msg="What services do you offer?">Services</button>
            <button class="gd-suggestion" data-msg="How much does it cost?">Pricing</button>
            <button class="gd-suggestion" data-msg="How does the process work?">Process</button>
            <button class="gd-suggestion" data-msg="Tell me about diaspora support">Diaspora</button>
        </div>
        <form class="gd-chat-input-wrap" id="gd-chat-form">
            <input type="text" id="gd-chat-input" placeholder="Ask about building in Ghana..." autocomplete="off" aria-label="Ask about building in Ghana">
            <button type="submit" class="gd-chat-send" aria-label="Send">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<footer>
    <div class="footer-top">
        <div class="footer-brand-col">
            <?php echo gabochie_svg_logo(); ?>
            <div class="footer-brand-name">GABOCHIE DESIGN</div>
            <div class="footer-brand-tag">Residential Architecture</div>
            <p class="footer-desc">AI-powered residential architecture and interior design for Ghana and the African diaspora.</p>
            <form class="footer-newsletter" onsubmit="return false;">
                <input type="email" placeholder="Your email" class="footer-newsletter-input" aria-label="Email for newsletter">
                <button type="submit" class="footer-newsletter-btn">Subscribe</button>
            </form>
            <div class="footer-socials">
                <?php
                $social_svgs = array(
                    'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                    'youtube' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
                    'behance' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.938 4.503c.702 0 1.34.06 1.92.188.577.13 1.07.33 1.485.61.41.28.733.65.96 1.12.225.47.34 1.05.34 1.73 0 .74-.17 1.36-.507 1.86-.338.5-.837.9-1.502 1.22.906.26 1.576.72 2.022 1.37.448.66.665 1.45.665 2.36 0 .75-.13 1.39-.41 1.93-.28.55-.67 1-1.16 1.35-.48.348-1.05.6-1.67.767-.62.16-1.26.25-1.92.25H0v-14.7h6.938zm-.36 5.49c.56 0 1.03-.13 1.4-.39.37-.26.55-.69.55-1.3 0-.34-.06-.62-.19-.84a1.38 1.38 0 0 0-.52-.54 2.22 2.22 0 0 0-.74-.28 3.62 3.62 0 0 0-.88-.09H3.27v3.44h3.31zm.12 5.79c.33 0 .65-.04.96-.12.31-.08.58-.22.81-.4.23-.18.41-.42.54-.71.13-.29.19-.66.19-1.11 0-.87-.24-1.5-.72-1.91-.48-.4-1.12-.6-1.92-.6H3.27v4.85h3.43zM15.2 4.94h5.53v1.27h-5.53V4.94zM20.09 12.56c-.16-.46-.39-.83-.71-1.12-.32-.29-.72-.51-1.2-.65-.48-.14-1.01-.21-1.59-.21-.58 0-1.11.07-1.59.21-.48.14-.88.36-1.2.65-.32.29-.55.66-.71 1.12-.16.46-.24.98-.24 1.57 0 .59.08 1.11.24 1.57.16.46.39.83.71 1.12.32.29.72.51 1.2.65.48.14 1.01.21 1.59.21.58 0 1.11-.07 1.59-.21.48-.14.88-.36 1.2-.65.32-.29.55-.66.71-1.12.16-.46.24-.98.24-1.57 0-.59-.08-1.11-.24-1.57zM19.04 14.6c-.08.22-.19.39-.33.52-.14.13-.31.2-.51.22-.2.02-.45-.03-.74-.16-.29-.13-.53-.34-.73-.63-.19-.29-.31-.67-.35-1.13-.04-.46-.01-.88.1-1.26.11-.38.27-.69.48-.94.21-.25.45-.42.73-.52.28-.1.55-.14.81-.11.26.03.48.12.66.26.18.14.32.33.42.57.1.24.14.53.12.87-.02.26-.1.48-.22.65h1.58c-.04-.76-.23-1.39-.55-1.89-.33-.5-.77-.88-1.34-1.13-.56-.26-1.22-.38-1.97-.38-.64 0-1.22.08-1.73.25-.52.17-.96.39-1.33.66-.37.27-.66.59-.87.95-.21.36-.35.75-.42 1.17-.07.42-.09.83-.06 1.24.03.41.12.8.27 1.17.15.37.36.69.64.97.28.28.63.5 1.05.66.42.16.91.24 1.47.24.72 0 1.32-.12 1.81-.36.49-.24.89-.58 1.19-1.02.31-.44.51-.97.6-1.59H19.04z"/></svg>',
                    'linkedin' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                    'tiktok' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
                );
                foreach ($socials as $name => $url) :
                    $svg = $social_svgs[$name] ?? '';
                ?>
                    <a href="<?php echo esc_url($url); ?>" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(ucfirst($name)); ?>"><?php echo $svg; ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="footer-col">
            <h3 class="footer-h">Services</h3>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/architectural-design/')); ?>">Architectural Design</a></li>
                <li><a href="<?php echo esc_url(home_url('/interior-design/')); ?>">Interior Design</a></li>
                <li><a href="<?php echo esc_url(home_url('/3d-visualization/')); ?>">3D Visualization</a></li>
                <li><a href="<?php echo esc_url(home_url('/project-management/')); ?>">Project Management</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3 class="footer-h">Company</h3>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/about/')); ?>">About Us</a></li>
                <li><a href="<?php echo esc_url(home_url('/portfolio/')); ?>">Portfolio</a></li>
                <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
                <li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3 class="footer-h">Resources</h3>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/building-cost-calculator/')); ?>">Cost Calculator</a></li>
                <li><a href="<?php echo esc_url(home_url('/diaspora/')); ?>">Diaspora Guide</a></li>
                <li><a href="<?php echo esc_url(home_url('/careers/')); ?>">Careers</a></li>
                <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
                <li><a href="<?php echo esc_url(home_url('/ghbc-tools/')); ?>">GhBC Tools</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3 class="footer-h">Get in Touch</h3>
            <ul>
                <li><strong><?php echo esc_html($phone); ?></strong></li>
                <li><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></li>
                <li><a href="https://wa.me/<?php echo esc_attr($whatsapp); ?>" target="_blank" rel="noopener noreferrer">WhatsApp Us</a></li>
                <li>Sakumono, Tema, Ghana</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="footer-copy">&copy; <?php echo date('Y'); ?> Gabochie Design. All rights reserved.</div>
        <div class="footer-legal">
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
            <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>">Terms of Service</a>
        </div>
        <div class="footer-flags">🇬🇭 🇺🇸 🇬🇧 🇳🇬</div>
    </div>
</footer>

<button class="back-to-top" aria-label="Back to top">↑</button>

<?php wp_footer(); ?>
</body>
</html>
