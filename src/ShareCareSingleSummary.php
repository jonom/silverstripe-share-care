<?php

namespace JonoM\ShareCare;
use SilverStripe\Core\Extension;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;

/**
 * Alternative to ShareCareFields extension that promotes the streamlined use of
 * a single summary for index page listings, search engine results and social
 * media shares.
 */
class ShareCareSingleSummary extends Extension
{
    private static $db = [
        'MetaDescription' => 'Text', // In case this isn't applied to a SiteTree sub-class
    ];

    private static $has_one = [
        'MetaImage' => Image::class,
    ];

    /**
     * Add and re-arrange CMS fields for streamlined summary editing.
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Remove all Meta fields
        $fields->removeByName('Metadata');
        // Add summary fields
        $fields->addFieldToTab('Root.Main', TextAreaField::create('MetaDescription', _t('JonoM\ShareCare\ShareCareSummary.SummaryTitle','Content summary'))
            ->setDescription(_t('JonoM\ShareCare\ShareCareSummary.SummaryDescription','Summarise the content of this page. This will be used for search engine results and social media so make it enticing.'))
            ->setAttribute('placeholder', $this->getOwner()->getDefaultOGDescription())
            ->setRows(2), 'Content');
        $imgFieldDescription = _t('JonoM\ShareCare\ShareCareSummary.ImageDescription','Choose an image to represent this page in listings and on social media.');
        if (!$this->getOwner()->MetaImageID && $this->getOwner()->isPublished()) {
            $imgFieldDescription .= " <i style=\"color:#ec720f\">" . _t('JonoM\ShareCare\ShareCareSummary.ImageDescriptionNotEmpty','For best results, please don\'t leave this empty.') . "</i>";
        }
        $fields->addFieldToTab('Root.Main', UploadField::create('MetaImage', _t('JonoM\ShareCare\ShareCareSummary.ImageTitle','Summary image'))
            ->setAllowedFileCategories('image')
            ->setAllowedMaxFileNumber(1)
            ->setDescription($imgFieldDescription), 'Content');
    }

    public function updateSettingsFields(FieldList $fields)
    {
        // Re-add MetaTag field in settings
        $fields->addFieldToTab('Root.Settings', TextareaField::create('ExtraMeta', _t('JonoM\ShareCare\ShareCareSummary.CustomMetaTags','Custom meta tags'))->setRows(3));
    }

    /**
     * The description that will be used in the 'og:description' open graph tag.
     * Use a custom value if set, or fallback to a default value.
     *
     * @return string
     */
    public function getOGDescription()
    {
        // Use MetaDescription if set
        if ($this->getOwner()->MetaDescription) {
            $description = trim((string) $this->getOwner()->MetaDescription);
            if (!empty($description)) {
                return $description;
            }
        }

        return $this->getOwner()->getDefaultOGDescription();
    }

    /**
     * The default/fallback value to be used in the 'og:description' open graph tag.
     *
     * @return string
     */
    public function getDefaultOGDescription()
    {
        // Fall back to Content
        if ($this->getOwner()->Content) {
            $description = trim((string) $this->getOwner()->obj('Content')->Summary(20, 5));
            if (!empty($description)) {
                return $description;
            }
        }

        return false;
    }

    /**
     * The Image object or absolute URL that will be used for the 'og:image' open graph tag.
     * Use a custom selection if set, or fallback to a default value.
     * Image size specs: https://developers.facebook.com/docs/sharing/best-practices#images.
     *
     * @return Image|string|false
     */
    public function getOGImage()
    {
        $ogImage = $this->getOwner()->MetaImage();
        if ($ogImage->exists()) {
            return ($ogImage->getWidth() > 1200) ? $ogImage->scaleWidth(1200) : $ogImage;
        }

        return $this->getOwner()->getDefaultOGImage();
    }
}
