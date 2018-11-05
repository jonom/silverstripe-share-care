<style type="text/css">
	.share-care-field p {
		margin: 0 0 8px 0;
	}
	.share-care-field div.message {
		margin: 0 0 10px 0 !important;
	}
	.share-care-field div.message p {
		margin: 0 0 4px 0;
	}
	.share-care-preview {
		float: left;
		padding-top: 8px;
		margin: 0 30px 0 0;
	}
	.share-care-preview .message {
		min-width: 200px;
		max-width: 300px;
		color: #666;
	}
	.share-care-preview img {
		max-width: 100%;
		max-height: 200px;
		width: auto;
		height: auto;
	}
	.share-care-field div.message p:last-child {
		margin: 0;
	}
	.share-care-description {
		color: #999;
	}
	.share-care-tests {
		clear: left;
	}
	.share-care-tests strong {
		margin-right: .5em;
	}
	.share-care-tests .separator {
		margin: 0 .5em;
		color: #ccc;
	}
</style>
<div class="field share-care-field">
	<label class="left"><% if $IncludePinterest %><%t JonoM\ShareCare\ShareCare.SharePreviews "Share Previews" %><% else %><%t JonoM\ShareCare\ShareCare.SharePreview "Share Preview" %><% end_if %></label>
	<div class="middleColumn">

		<div class="share-care-preview">
			<p>Facebook / Twitter / Google+</p>
			<div class="message">
				<% if $OGImage %>
					<p>
						<% if $OGImage.scaleWidth(300) %>
							$OGImage.scaleWidth(300)
						<% else %>
							<img src="$OGImage" />
						<% end_if %>
					</p>
				<% end_if %>
				<p class="share-care-title"><strong>$OGTitle</strong></p>
				<p class="share-care-description">$OGDescription</p>
			</div>
		</div>

		<% if $IncludePinterest %>

			<div class="share-care-preview">
				<p>Pinterest</p>
				<div class="message">
					<% if $PinterestImage %>
						<p>
							<% if $PinterestImage.scaleWidth(300) %>
								$PinterestImage.scaleWidth(300)
							<% else %>
								<img src="$PinterestImage" />
							<% end_if %>
						</p>
					<% end_if %>
					<p class="share-care-title">$OGTitle</p>
				</div>
			</div>

		<% end_if %>

		<p class="share-care-tests"><strong>Share:</strong>
			<a href="$FacebookShareLink" target="_blank">Facebook</a>
			<a href="https://developers.facebook.com/tools/debug/og/object?q=$AbsoluteLink.URLATT" target="_blank">(<%t JonoM\ShareCare\ShareCare.Debug "Debug" %>)</a>
			<span class="separator">|</span> <a href="$TwitterShareLink" target="_blank">Twitter</a>
			<% if $IncludeTwitter %><a href="https://cards-dev.twitter.com/validator" target="_blank">(<%t JonoM\ShareCare\ShareCare.Debug "Debug" %>)</a><% end_if %>
			<span class="separator">|</span> <a href="$GooglePlusShareLink" target="_blank">Google+</a>
			<span class="separator">|</span> <a href="$PinterestShareLink" target="_blank">Pinterest</a>
			<span class="separator">|</span> <a href="$LinkedInShareLink" target="_blank">LinkedIn</a>
		</p>

	</div>
	<span class="description"><%t JonoM\ShareCare\ShareCare.SaveNotice "Previews will be updated after saving. Changes need to be published before they take effect." %></span>
</div>
