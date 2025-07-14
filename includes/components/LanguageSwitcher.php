<?php
namespace CollectionManager;

/**
 * Language Switcher Component
 * Renders a dropdown for switching languages
 */
class LanguageSwitcher 
{
    /**
     * Render language switcher dropdown
     */
    public static function render($style = 'dropdown', $showFlags = true, $showLabels = true) 
    {
        if (!I18nHelper::isEnabled()) {
            return '';
        }
        
        $languages = I18nHelper::getAvailableLanguages();
        $currentLanguage = I18nHelper::getCurrentLanguage();
        $currentLanguageInfo = I18nHelper::getLanguageInfo($currentLanguage);
        
        if (count($languages) <= 1) {
            return '';
        }
        
        switch ($style) {
            case 'dropdown':
                return self::renderDropdown($languages, $currentLanguage, $currentLanguageInfo, $showFlags, $showLabels);
            case 'buttons':
                return self::renderButtons($languages, $currentLanguage, $showFlags, $showLabels);
            case 'select':
                return self::renderSelect($languages, $currentLanguage, $showLabels);
            default:
                return self::renderDropdown($languages, $currentLanguage, $currentLanguageInfo, $showFlags, $showLabels);
        }
    }
    
    /**
     * Render dropdown style switcher
     */
    private static function renderDropdown($languages, $currentLanguage, $currentLanguageInfo, $showFlags, $showLabels) 
    {
        $html = '<div class="dropdown language-switcher">';
        $html .= '<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">';
        
        if ($showFlags) {
            $html .= self::getLanguageFlag($currentLanguage) . ' ';
        }
        
        if ($showLabels) {
            $html .= htmlspecialchars($currentLanguageInfo['native_name'] ?? strtoupper($currentLanguage));
        } else {
            $html .= strtoupper($currentLanguage);
        }
        
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu" aria-labelledby="languageDropdown">';
        
        foreach ($languages as $language) {
            $isActive = $language['code'] === $currentLanguage;
            $activeClass = $isActive ? 'active' : '';
            
            $html .= '<li><a class="dropdown-item ' . $activeClass . '" href="language.php?lang=' . urlencode($language['code']) . '">';
            
            if ($showFlags) {
                $html .= self::getLanguageFlag($language['code']) . ' ';
            }
            
            if ($showLabels) {
                $html .= htmlspecialchars($language['native_name']);
            } else {
                $html .= strtoupper($language['code']);
            }
            
            if ($isActive) {
                $html .= ' <i class="bi bi-check"></i>';
            }
            
            $html .= '</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render button style switcher
     */
    private static function renderButtons($languages, $currentLanguage, $showFlags, $showLabels) 
    {
        $html = '<div class="btn-group language-switcher" role="group">';
        
        foreach ($languages as $language) {
            $isActive = $language['code'] === $currentLanguage;
            $buttonClass = $isActive ? 'btn btn-primary btn-sm' : 'btn btn-outline-primary btn-sm';
            
            $html .= '<a href="language.php?lang=' . urlencode($language['code']) . '" class="' . $buttonClass . '">';
            
            if ($showFlags) {
                $html .= self::getLanguageFlag($language['code']) . ' ';
            }
            
            if ($showLabels) {
                $html .= htmlspecialchars($language['native_name']);
            } else {
                $html .= strtoupper($language['code']);
            }
            
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render select style switcher
     */
    private static function renderSelect($languages, $currentLanguage, $showLabels) 
    {
        $html = '<select class="form-select form-select-sm language-switcher" onchange="window.location.href=\'language.php?lang=\' + this.value">';
        
        foreach ($languages as $language) {
            $selected = $language['code'] === $currentLanguage ? 'selected' : '';
            $label = $showLabels ? $language['native_name'] : strtoupper($language['code']);
            
            $html .= '<option value="' . htmlspecialchars($language['code']) . '" ' . $selected . '>';
            $html .= htmlspecialchars($label);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    /**
     * Get language flag emoji or icon
     */
    private static function getLanguageFlag($languageCode) 
    {
        $flags = [
            'nl' => '🇳🇱',
            'en' => '🇬🇧',
            'de' => '🇩🇪',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'ru' => '🇷🇺',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
            'ko' => '🇰🇷',
            'ar' => '🇸🇦',
            'he' => '🇮🇱',
            'tr' => '🇹🇷',
            'pl' => '🇵🇱',
            'sv' => '🇸🇪',
            'da' => '🇩🇰',
            'no' => '🇳🇴',
            'fi' => '🇫🇮'
        ];
        
        return $flags[$languageCode] ?? '🌐';
    }
    
    /**
     * Render inline text style switcher
     */
    public static function renderInline($separator = ' | ') 
    {
        if (!I18nHelper::isEnabled()) {
            return '';
        }
        
        $languages = I18nHelper::getAvailableLanguages();
        $currentLanguage = I18nHelper::getCurrentLanguage();
        
        if (count($languages) <= 1) {
            return '';
        }
        
        $links = [];
        foreach ($languages as $language) {
            $isActive = $language['code'] === $currentLanguage;
            
            if ($isActive) {
                $links[] = '<strong>' . htmlspecialchars($language['native_name']) . '</strong>';
            } else {
                $links[] = '<a href="language.php?lang=' . urlencode($language['code']) . '">' . htmlspecialchars($language['native_name']) . '</a>';
            }
        }
        
        return '<div class="language-switcher-inline">' . implode($separator, $links) . '</div>';
    }
    
    /**
     * Render JavaScript-enhanced switcher
     */
    public static function renderWithJS($containerId = 'language-switcher') 
    {
        if (!I18nHelper::isEnabled()) {
            return '';
        }
        
        $html = '<div id="' . $containerId . '" class="language-switcher"></div>';
        
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            loadLanguageSwitcher("' . $containerId . '");
        });
        
        function loadLanguageSwitcher(containerId) {
            fetch("language.php?action=get_languages")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderLanguageSwitcher(containerId, data);
                    }
                })
                .catch(error => console.error("Error loading languages:", error));
        }
        
        function renderLanguageSwitcher(containerId, data) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            let html = `<div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    ${getCurrentLanguageDisplay(data)}
                </button>
                <ul class="dropdown-menu">`;
            
            data.languages.forEach(lang => {
                const isActive = lang.code === data.current_language;
                const activeClass = isActive ? "active" : "";
                html += `<li><a class="dropdown-item ${activeClass}" href="#" onclick="switchLanguage(\'${lang.code}\')">
                    ${getLanguageFlag(lang.code)} ${lang.native_name}
                    ${isActive ? \'<i class="bi bi-check"></i>\' : \'\'}
                </a></li>`;
            });
            
            html += "</ul></div>";
            container.innerHTML = html;
        }
        
        function getCurrentLanguageDisplay(data) {
            const currentLang = data.languages.find(lang => lang.code === data.current_language);
            return `${getLanguageFlag(data.current_language)} ${currentLang ? currentLang.native_name : data.current_language.toUpperCase()}`;
        }
        
        function getLanguageFlag(langCode) {
            const flags = {
                "nl": "🇳🇱", "en": "🇬🇧", "de": "🇩🇪", "fr": "🇫🇷", "es": "🇪🇸",
                "it": "🇮🇹", "pt": "🇵🇹", "ru": "🇷🇺", "zh": "🇨🇳", "ja": "🇯🇵",
                "ko": "🇰🇷", "ar": "🇸🇦", "he": "🇮🇱", "tr": "🇹🇷", "pl": "🇵🇱"
            };
            return flags[langCode] || "🌐";
        }
        
        function switchLanguage(languageCode) {
            fetch("language.php?action=switch&lang=" + languageCode, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert("Error switching language: " + data.error);
                }
            })
            .catch(error => {
                console.error("Error switching language:", error);
                // Fallback to regular navigation
                window.location.href = "language.php?lang=" + languageCode;
            });
        }
        </script>';
        
        return $html;
    }
    
    /**
     * Get CSS for RTL support
     */
    public static function getRTLCSS() 
    {
        return '
        /* RTL Support */
        [dir="rtl"] {
            text-align: right;
            direction: rtl;
        }
        
        [dir="rtl"] .navbar-brand {
            margin-right: 0;
            margin-left: 1rem;
        }
        
        [dir="rtl"] .dropdown-menu {
            right: 0;
            left: auto;
        }
        
        [dir="rtl"] .btn-group > .btn:not(:last-child):not(.dropdown-toggle) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-top-left-radius: .375rem;
            border-bottom-left-radius: .375rem;
        }
        
        [dir="rtl"] .btn-group > .btn:not(:first-child) {
            margin-left: 0;
            margin-right: -1px;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: .375rem;
            border-bottom-right-radius: .375rem;
        }
        
        [dir="rtl"] .me-2 {
            margin-left: 0.5rem !important;
            margin-right: 0 !important;
        }
        
        [dir="rtl"] .ms-2 {
            margin-right: 0.5rem !important;
            margin-left: 0 !important;
        }
        ';
    }
} 