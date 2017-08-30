<?php
/**
 * Provide default fields and method customisations to complement Open Graph
 * module with minimal setup.
 */
class ShareCareFields extends DataExtension
{
    private static $db = array(
        'OGTitleCustom' => 'Varchar(100)',
        'OGDescriptionCustom' => 'Varchar(150)',
    );

    private static $has_one = array(
        'OGImageCustom' => 'Image',
        'PinterestImageCustom' => 'Image',
    );

    /**
     * Add CMS fields to allow setting of custom open graph values.
     */
    public function updateCMSFields(FieldList $fields)
    {
		$msg = _t('ShareCareFields.CMSMessage', 'The preview is automatically generated from your content. You can override the default values using these fields:');
		$tab = 'Root.' . _t('ShareCare.TabName','Share');
        if ($msg) {
            $fields->addFieldToTab($tab, new LiteralField('ShareCareFieldsMessage',
                '<div class="message notice"><p>'.$msg.'</p></div>'));
        }
        $fields->addFieldToTab($tab, TextField::create('OGTitleCustom', _t('ShareCareFields.ShareTitle','Share title'))
            ->setAttribute('placeholder', $this->owner->getDefaultOGTitle())
            ->setMaxLength(90));
        $fields->addFieldToTab($tab, TextAreaField::create('OGDescriptionCustom', _t('ShareCareFields.ShareDescription','Share description'))
            ->setAttribute('placeholder', $this->owner->getDefaultOGDescription())
            ->setRows(2));
        $fields->addFieldToTab($tab, UploadField::create('OGImageCustom', _t('ShareCareFields.ShareImage','Share image'))
            ->setAllowedFileCategories('image')
            ->setAllowedMaxFileNumber(1)
			->setDescription(_t('ShareCareFields.ShareImageRatio','{Link}Optimum image ratio</a> is 1.91:1. (1200px wide by 630px tall or better)', ['Link'=>'<a href="https://developers.facebook.com/docs/sharing/best-practices#images" target="_blank">'])));
        if (Config::inst()->get('ShareCare', 'pinterest')) {
            $fields->addFieldToTab($tab, UploadField::create('PinterestImageCustom', _t('ShareCareFields.PinterestImage','Pinterest image'))
                ->setAllowedFileCategories('image')
                ->setAllowedMaxFileNumber(1)
                ->setDescription(_t('ShareCareFields.PinterestImageDescription','Square/portrait or taller images look best on Pinterest. This image should be at least 750px wide.')));
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
        return ($this->owner->OGTitleCustom) ? $this->owner->OGTitleCustom : $this->owner->getDefaultOGTitle();
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
        if ($this->owner->OGDescriptionCustom) {
            $description = trim($this->owner->OGDescriptionCustom);
            if (!empty($description)) {
                return $description;
            }
        }

        return $this->owner->getDefaultOGDescription();
    }

    /**
     * The default/fallback value to be used in the 'og:description' open graph tag.
     *
     * @return string
     */
    public function getDefaultOGDescription()
    {
        // Use MetaDescription if set
        if ($this->owner->MetaDescription) {
            $description = trim($this->owner->MetaDescription);
            if (!empty($description)) {
                return $description;
            }
        }

        // Fall back to Content
        if ($this->owner->Content) {
            $description = trim($this->owner->obj('Content')->Summary(20, 5));
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
        $ogImage = $this->owner->OGImageCustom();
        if ($ogImage->exists()) {
            return ($ogImage->getWidth() > 1200) ? $ogImage->setWidth(1200) : $ogImage;
        }

        return $this->owner->getDefaultOGImage();
    }

  /**
   * Get an Image object to be used in the 'Pin it' ($PinterestShareLink) link.
   * Image size specs: https://developers.pinterest.com/pin_it/.
   *
   * @return Image|null
   */
  public function getPinterestImage()
  {
      $pinImage = $this->owner->PinterestImageCustom();
      if ($pinImage->exists()) {
          return ($pinImage->getWidth() > 1200) ? $pinImage->setWidth(1200) : $pinImage;
      }

      return $this->owner->getOGImage();
  }
}
