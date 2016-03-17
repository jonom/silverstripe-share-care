<?php
/**
 * ShareCare class.
 * Provide previews for sharing content based on Open Graph tags.
 *
 * @extends DataExtension
 */
class ShareCare extends DataExtension
{
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
     * Message shown at top of Share tab. Set to false to disable.
     *
     * @var string
     * @config
     */
    private static $cms_message = 'When this page is shared by people on social media it will look something like this:';

    /**
     * Add a Social Media tab with a preview of share appearance to the CMS.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $msg = Config::inst()->get('ShareCare', 'cms_message');
        if ($msg) {
            $fields->addFieldToTab('Root.Share', new LiteralField('ShareCareMessage',
                '<div class="message notice"><p>'.$msg.'</p></div>'));
        }
        $fields->addFieldToTab('Root.Share', new LiteralField('ShareCarePreview',
            $this->owner->RenderWith('ShareCarePreview', array(
                'IncludeTwitter' => Config::inst()->get('ShareCare', 'twitter_card'),
                'IncludePinterest' => Config::inst()->get('ShareCare', 'pinterest'),
        ))));
    }

    /**
     * Ensure public URLs are re-scraped by Facebook after publishing.
     */
    public function onAfterPublish()
    {
        $this->owner->clearFacebookCache();
    }

    /**
     * Ensure public URLs are re-scraped by Facebook after writing.
     */
    public function onAfterWrite()
    {
        if (!$this->owner->hasMethod('doPublish')) {
            $this->owner->clearFacebookCache();
        }
    }

    /**
     * Tell Facebook to re-scrape this URL, if it is accessible to the public.
     *
     * @return RestfulService_Response
     */
    public function clearFacebookCache()
    {
        if ($this->owner->hasMethod('AbsoluteLink')) {
            $anonymousUser = new Member();
            if ($this->owner->can('View', $anonymousUser)) {
                $fetch = new RestfulService('https://graph.facebook.com/');
                $fetch->setQueryString(array(
                    'id' => $this->owner->AbsoluteLink(),
                    'scrape' => true,
                ));

                return $fetch->request();
            }
        }
    }

    /**
     * Generate a URL to share this content on Facebook.
     *
     * @return string|false
     */
    public function FacebookShareLink()
    {
        if (!$this->owner->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = rawurlencode($this->owner->AbsoluteLink());

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
        if (!$this->owner->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = rawurlencode($this->owner->AbsoluteLink());
        $text = rawurlencode($this->owner->getOGTitle());

        return ($pageURL) ? "https://twitter.com/intent/tweet?text=$text&url=$pageURL" : false;
    }

    /**
     * Generate a URL to share this content on Google+
     * Specs: https://developers.google.com/+/web/snippet/article-rendering.
     *
     * @return string|false
     */
    public function GooglePlusShareLink()
    {
        if (!$this->owner->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = rawurlencode($this->owner->AbsoluteLink());

        return ($pageURL) ? "https://plus.google.com/share?url=$pageURL" : false;
    }

    /**
     * Generate a URL to share this content on Pinterest
     * Specs: https://developers.pinterest.com/pin_it/.
     *
     * @return string|false
     */
    public function PinterestShareLink()
    {
        $pinImage = ($this->owner->hasMethod('getPinterestImage')) ? $this->owner->getPinterestImage() : $this->owner->getOGImage();
        if ($pinImage) {
            // OGImage may be an Image object or an absolute URL
            $imageURL = rawurlencode((is_string($pinImage)) ? $pinImage : $pinImage->getAbsoluteURL());
            $pageURL = rawurlencode($this->owner->AbsoluteLink());
            $description = rawurlencode($this->owner->getOGTitle());
            // Combine Title, link and image in to rich link
            return "http://www.pinterest.com/pin/create/button/?url=$pageURL&media=$imageURL&description=$description";
        }

        return false;
    }

    /**
     * Generate a 'mailto' URL to share this content via Email.
     *
     * @return string|false
     */
    public function EmailShareLink()
    {
        if (!$this->owner->hasMethod('AbsoluteLink')) {
            return false;
        }
        $pageURL = $this->owner->AbsoluteLink();
        $subject = rawurlencode('Thought you might like this');
        $body = rawurlencode("Thought of you when I found this: $pageURL");

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
        $title = htmlspecialchars($this->owner->getOGTitle());
        $description = htmlspecialchars($this->owner->getOGDescription());
        $tMeta = "\n<meta name=\"twitter:title\" content=\"$title\">"
        ."\n<meta name=\"twitter:description\" content=\"$description\">";

        // If we have a big enough image, include an image tag.
        $image = $this->owner->getOGImage();
        // $image may be a string - don't generate a specific twitter tag
        // in that case as it is probably the default resource.
        if ($image instanceof Image && $image->getWidth() >= 280) {
            $imageURL = htmlspecialchars(Director::absoluteURL($image->Link()));
            $tMeta .= "\n<meta name=\"twitter:card\" content=\"summary_large_image\">"
            ."\n<meta name=\"twitter:image\" content=\"$imageURL\">";
        }

        $username = Config::inst()->get('ShareCare', 'twitter_username');
        if ($username) {
            $tMeta .= "\n<meta name=\"twitter:site\" content=\"@$username\">"
            ."\n<meta name=\"twitter:creator\" content=\"@$username\">";
        }

        return $tMeta;
    }

    /**
     * Extension hook for including Twitter Card markup.
     */
    public function MetaTags(&$tags)
    {
        if (Config::inst()->get('ShareCare', 'twitter_card')) {
            $tags .= $this->owner->getTwitterMetaTags();
        }
    }

    /**
     * The default/fallback value to be used in the 'og:title' open graph tag.
     *
     * @return string
     */
    public function getDefaultOGTitle()
    {
        return $this->owner->getTitle();
    }

    /**
     * The default/fallback Image object or absolute URL to be used in the 'og:image' open graph tag.
     *
     * @return Image|string|false
     */
    public function getDefaultOGImage()
    {
        // We don't want to use the SilverStripe logo, so let's use a favicon if available.
        return (file_exists(BASE_PATH.'/apple-touch-icon.png'))
            ? Director::absoluteURL('apple-touch-icon.png', true)
            : false;
    }
}
