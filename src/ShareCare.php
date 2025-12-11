<?php

namespace JonoM\ShareCare;

use SilverStripe\Core\Extension;
use Exception;
use GuzzleHttp\Client;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Member;

/**
 * ShareCare class.
 * Provide previews for sharing content based on Open Graph tags.
 *
 * @extends Extension<DataObject>
 */
class ShareCare extends Extension
{
    use Configurable;

    /**
     * Twitter username to be attributed as owner/author of this page.
     * Example: 'mytwitterhandle'.
     *
     * @var string
     * @config
     */
    private static $twitter_username = '';

    /**
     * Whether or not to generate a twitter card for this page.
     * More info: https://dev.twitter.com/cards/overview.
     *
     * @var bool
     * @config
     */
    private static $twitter_card = true;

    /**
     * Whether or not to enable a Pinterest preview and fields.
     * You need to be using the $PinterestShareLink for this to be useful.
     *
     * @var bool
     * @config
     */
    private static $pinterest = false;

    /**
     * Allow userland config to conditionally permit cache-clearing for Facebook
     * after every page-write. The default is true for BC.
     *
     * @var bool
     */
    private static $enable_facebook_cache_clear = true;

    /**
     * Add a Social Media tab with a preview of share appearance to the CMS.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $msg = _t('JonoM\ShareCare\ShareCare.CMSMessage', 'When this page is shared by people on social media it will look something like this:');
        $tab = 'Root.' . _t('JonoM\ShareCare\ShareCare.TabName', 'Share');
        if ($msg) {
            $fields->addFieldToTab($tab, LiteralField::create('ShareCareMessage', '<div class="message notice"><p>' . $msg . '</p></div>'));
        }
        $fields->addFieldToTab($tab, LiteralField::create('ShareCarePreview', $this->getOwner()->RenderWith('ShareCarePreview', [
            'IncludeTwitter' => self::config()->get('twitter_card'),
            'IncludePinterest' => self::config()->get('pinterest')
        ])));
    }

    /**
     * Ensure public URLs are re-scraped by Facebook after publishing.
     */
    public function onAfterPublish()
    {
        $this->getOwner()->clearFacebookCache();
    }

    /**
     * Ensure public URLs are re-scraped by Facebook after writing.
     */
    public function onAfterWrite()
    {
        if (!$this->getOwner()->hasMethod('doPublish')) {
            $this->getOwner()->clearFacebookCache();
        }
    }

    /**
     * Tell Facebook to re-scrape this URL, if it is accessible to the public.
     */
    public function clearFacebookCache()
    {
        if ($this->doClearFacebookCache() && $this->getOwner()->hasMethod('AbsoluteLink')) {
            $anonymousUser = Member::create();
            if ($this->getOwner()->can('View', $anonymousUser)) {
                $client = new Client();
                $access_token = Environment::getEnv('SS_SHARECARE_FBACCESSTOKEN') ?: $this->config()->get('facebook_access_token');
                try {
                    $client->request('GET', 'https://graph.facebook.com/', [
                        'query' => [
                            'id' => $this->getOwner()->AbsoluteLink(),
                            'scrape' => true,
                            'access_token' => $access_token
                        ]
                    ]);
                } catch (Exception $e) {
                    user_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
    }

    /**
     * Decide wether or not we should be clearing Facebook's cache.
     *
     * @return boolean
     */
    public function doClearFacebookCache()
    {
        return $this->config()->get('enable_facebook_cache_clear');
    }

    /**
     * Generate a URL to share this content on Facebook.
     *
     * @return string|false
     */
    public function FacebookShareLink()
    {
        if (!$this->getOwner()->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = rawurlencode((string) $this->getOwner()->AbsoluteLink());

        return ($pageURL) ? "https://www.facebook.com/sharer/sharer.php?u=$pageURL" : false;
    }

    /**
     * Generate a URL to share this content on Twitter
     * Specs: https://dev.twitter.com/web/tweet-button/web-intent.
     *
     * @return string|false
     */
    public function TwitterShareLink()
    {
        if (!$this->getOwner()->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = rawurlencode((string) $this->getOwner()->AbsoluteLink());
        $text = rawurlencode((string) $this->getOwner()->getOGTitle());

        return ($pageURL) ? "https://twitter.com/intent/tweet?text=$text&url=$pageURL" : false;
    }

    /**
     * Generate a URL to share this content on Pinterest
     * Specs: https://developers.pinterest.com/pin_it/.
     *
     * @return string|false
     */
    public function PinterestShareLink()
    {
        $pinImage = ($this->getOwner()->hasMethod('getPinterestImage')) ? $this->getOwner()->getPinterestImage() : $this->getOwner()->getOGImage();
        if ($pinImage) {
            // OGImage may be an Image object or an absolute URL
            $imageURL = rawurlencode((string) (is_string($pinImage) ? $pinImage : $pinImage->getAbsoluteURL()));
            $pageURL = rawurlencode((string) $this->getOwner()->AbsoluteLink());
            $description = rawurlencode((string) $this->getOwner()->getOGTitle());
            // Combine Title, link and image in to rich link
            return "http://www.pinterest.com/pin/create/button/?url=$pageURL&media=$imageURL&description=$description";
        }

        return false;
    }

    /**
     * Generate a URL to share this content on LinkedIn
     * specs: https://developer.linkedin.com/docs/share-on-linkedin
     *
     * @return string|string
     */
    public function LinkedInShareLink()
    {
        if (!$this->getOwner()->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = rawurlencode((string) $this->getOwner()->AbsoluteLink());
        $title = rawurlencode((string) $this->getOwner()->getOGTitle());
        $description = rawurlencode((string) $this->getOwner()->getOGDescription());
        $source = rawurlencode((string) $this->getOwner()->getOGSiteName());

        return "https://www.linkedin.com/shareArticle?mini=true&url=$pageURL&title=$title&summary=$description&source=$source";
    }

    /**
     * Generate a 'mailto' URL to share this content via Email.
     *
     * @return string|false
     */
    public function EmailShareLink()
    {
        if (!$this->getOwner()->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = $this->getOwner()->AbsoluteLink();
        $subject = rawurlencode(_t('JonoM\ShareCare\ShareCare.EmailSubject', 'Thought you might like this'));
        $body = rawurlencode(_t('JonoM\ShareCare\ShareCare.EmailBody', 'Thought of you when I found this: {URL}', ['URL' => $pageURL]));

        return ($pageURL) ? "mailto:?subject=$subject&body=$body" : false;
    }

    /**
     * Generate meta tag markup for Twitter Cards
     * Specs: https://dev.twitter.com/cards/types/summary-large-image.
     *
     * @return string
     */
    public function getTwitterMetaTags()
    {
        $title = htmlspecialchars((string) $this->getOwner()->getOGTitle());
        $description = htmlspecialchars((string) $this->getOwner()->getOGDescription());
        $tMeta = "\n<meta name=\"twitter:title\" content=\"$title\">"
            . "\n<meta name=\"twitter:description\" content=\"$description\">";

        // If we have a big enough image, include an image tag.
        $image = $this->getOwner()->getOGImage();
        // $image may be a string - don't generate a specific twitter tag
        // in that case as it is probably the default resource.
        if (($image instanceof Image || $image instanceof DBFile) && $image->getWidth() >= 280) {
            $imageURL = htmlspecialchars(Director::absoluteURL($image->Link()));
            $tMeta .= "\n<meta name=\"twitter:card\" content=\"summary_large_image\">"
                . "\n<meta name=\"twitter:image\" content=\"$imageURL\">";
        }

        $username = self::config()->get('twitter_username');
        if ($username) {
            $tMeta .= "\n<meta name=\"twitter:site\" content=\"@$username\">"
                . "\n<meta name=\"twitter:creator\" content=\"@$username\">";
        }

        return $tMeta;
    }

    /**
     * Extension hook for including Twitter Card markup.
     */
    public function MetaTags(&$tags)
    {
        if (self::config()->get('twitter_card')) {
            $tags .= $this->getOwner()->getTwitterMetaTags();
        }
    }

    /**
     * The default/fallback value to be used in the 'og:title' open graph tag.
     *
     * @return string
     */
    public function getDefaultOGTitle()
    {
        return (string) $this->getOwner()->getTitle();
    }

    /**
     * The default/fallback Image object or absolute URL to be used in the 'og:image' open graph tag.
     *
     * @return Image|string|false
     */
    public function getDefaultOGImage()
    {
        // We don't want to use the SilverStripe logo, so let's use a favicon if available.
        return (file_exists(PUBLIC_PATH . '/apple-touch-icon.png'))
            ? Director::absoluteURL('apple-touch-icon.png', true)
            : false;
    }
}
