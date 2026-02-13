<?php

namespace JonoM\ShareCare;
use SilverStripe\Core\Extension;
use JonoM\ShareCare\ShareCare;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;

/**
 * Provide default fields and method customisations to complement Open Graph
 * module with minimal setup.
 */
class ShareCareFields extends Extension
{
    private static $db = [
        'OGTitleCustom' => 'Varchar(100)',
        'OGDescriptionCustom' => 'Varchar(150)',
    ];

    private static $has_one = [
        'OGImageCustom' => Image::class,
        'PinterestImageCustom' => Image::class,
    ];

	private static $owns = [
		'OGImageCustom',
		'PinterestImageCustom'
	];

    private static $scaffold_cms_fields_settings = [
        'ignoreFields' => [
            'OGTitleCustom',
            'OGDescriptionCustom',
            'OGImageCustom',
            'PinterestImageCustom',
        ],
    ];

    /**
     * Add CMS fields to allow setting of custom open graph values.
     */
    public function updateCMSFields(FieldList $fields)
    {
        $msg = _t('JonoM\ShareCare\ShareCareFields.CMSMessage', 'The preview is automatically generated from your content. You can override the default values using these fields:');
        $tab = 'Root.' . _t('JonoM\ShareCare\ShareCare.TabName', 'Share');
        if ($msg) {
            $fields->addFieldToTab($tab, LiteralField::create('ShareCareFieldsMessage', '<div class="message notice"><p>' . $msg . '</p></div>'));
        }
        $fields->addFieldToTab($tab, TextField::create('OGTitleCustom', _t('JonoM\ShareCare\ShareCareFields.ShareTitle', 'Share title'))
            ->setAttribute('placeholder', $this->getOwner()->getDefaultOGTitle())
            ->setMaxLength(90));
        $fields->addFieldToTab($tab, TextAreaField::create('OGDescriptionCustom', _t('JonoM\ShareCare\ShareCareFields.ShareDescription', 'Share description'))
            ->setAttribute('placeholder', $this->getOwner()->getDefaultOGDescription())
            ->setRows(2));
        $fields->addFieldToTab($tab, UploadField::create('OGImageCustom', _t('JonoM\ShareCare\ShareCareFields.ShareImage', 'Share image'))
            ->setAllowedFileCategories('image')
            ->setAllowedMaxFileNumber(1)
            ->setDescription(_t('JonoM\ShareCare\ShareCareFields.ShareImageRatio', '{Link}Optimum image ratio</a> is 1.91:1. (1200px wide by 630px tall or better)', ['Link' => '<a href="https://developers.facebook.com/docs/sharing/best-practices#images" target="_blank">'])));
        if (ShareCare::config()->get('pinterest')) {
            $fields->addFieldToTab($tab, UploadField::create('PinterestImageCustom', _t('ShareCareFields.PinterestImage', 'Pinterest image'))
                ->setAllowedFileCategories('image')
                ->setAllowedMaxFileNumber(1)
                ->setDescription(_t('JonoM\ShareCare\ShareCareFields.PinterestImageDescription', 'Square/portrait or taller images look best on Pinterest. This image should be at least 750px wide.')));
        }
    }

    /**
     * The title that will be used in the 'og:title' open graph tag.
     * Use a custom value if set, or fallback to a default value.
     *
     * @return string
     */
    public function getOGTitle()
    {
        return $this->getOwner()->OGTitleCustom ?: $this->getOwner()->getDefaultOGTitle();
    }

    /**
     * The description that will be used in the 'og:description' open graph tag.
     * Use a custom value if set, or fallback to a default value.
     *
     * @return string
     */
    public function getOGDescription()
    {
        // Use OG Description if set
        if ($this->getOwner()->OGDescriptionCustom) {
            $description = trim((string) $this->getOwner()->OGDescriptionCustom);
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
        // Use MetaDescription if set
        if ($this->getOwner()->MetaDescription) {
            $description = trim((string) $this->getOwner()->MetaDescription);
            if (!empty($description)) {
                return $description;
            }
        }

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
        $ogImage = $this->getOwner()->OGImageCustom();
        if ($ogImage->exists()) {
            return ($ogImage->getWidth() > 1200) ? $ogImage->scaleWidth(1200) : $ogImage;
        }

        return $this->getOwner()->getDefaultOGImage();
    }

    /**
     * Get an Image object to be used in the 'Pin it' ($PinterestShareLink) link.
     * Image size specs: https://developers.pinterest.com/pin_it/.
     *
     * @return Image|null
     */
    public function getPinterestImage()
    {
        $pinImage = $this->getOwner()->PinterestImageCustom();
        if ($pinImage->exists()) {
            return ($pinImage->getWidth() > 1200) ? $pinImage->scaleWidth(1200) : $pinImage;
        }

        return $this->getOwner()->getOGImage();
    }
}
