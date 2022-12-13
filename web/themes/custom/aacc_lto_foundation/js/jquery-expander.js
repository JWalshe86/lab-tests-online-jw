/**
 * @file
 * Provides functionality to expand/collapse expandable text Paragraph items.
 *
 */
(function ($, Drupal) {

  /**
   * Behavior for Text Paragraph Expander.
   */
  Drupal.behaviors.textExpand = {
    attach: function(context, settings) {
      $(context).find('.expandable-text-wrapper').find('.expandable-text').once('toggleTextArea').each(function () {
        var $this = $(this);
        var slice_point = 435;
        var expand_prefix = '... ';
        var widow_value = 100;
        if ($this.closest(".paragraph--type--expandable").hasClass("no-teaser")) {
          slice_point = 0;
          expand_prefix = '';
          widow_value = 0;
        }
        var bodyClasses = $('body').attr('class');
        var isUSsite = false;
        if (bodyClasses.search("site--lto-us") >= 0) {
          isUSsite = true;
        }
        $this.expander({
          slicePoint: slice_point,
          preserveWords: true,
          normalizeWhitespace: true,
          widow: widow_value,
          moreClass: 'expand-read-more',
          lessClass: 'expand-read-less',
          expandText: '',
          expandPrefix: expand_prefix,
          userCollapseText: '',
          collapseSpeed: 50,
          startExpanded: isUSsite,
          onSlice: function(){
            if (isUSsite) {
              $(this).siblings('.collapse-wrapper').removeClass('hide');
            }
            else {
              // Display the expand link only if the text has been sliced.
              $(this).siblings('.expand-wrapper').removeClass('hide');
            }
          }
        });
      });

      // Expand/Collapse wrappers were created in the template to make the link text translatable.
      $(context).find('.expand-wrapper').once('expandTextArea').each(function (e) {
        var $this = $(this);
        $this.on('click', function(){
          // Find and click the plugin's expand link to expand the text.
          $this.siblings('.expandable-text').find('.expand-read-more a').click();
          // Display the collapse link from our template.
          $this.siblings('.collapse-wrapper').removeClass('hide');
          // Hide our expand link.
          $this.addClass('hide');
          $this.siblings('.expander-focus')[0].scrollIntoView();
          $this.siblings('.expander-focus').focus();
        });
      });

      // Expand/Collapse wrappers were created in the template to make the link text translatable.
      $(context).find('.collapse-wrapper').once('collapseTextArea').each(function (e) {
        var $this = $(this);
        $this.on('click', function(){
          // Find and click the plugin's collapse link to expand the text.
          $this.siblings('.expandable-text').find('.expand-read-less a').click();
          // Display the expand link from our template.
          $this.siblings('.expand-wrapper').removeClass('hide');
          // Hide our collapse link.
          $this.addClass('hide');
          $this.siblings('.expander-focus')[0].scrollIntoView();
          $this.siblings('.expander-focus').focus();
        });
      });
    }
  };

})(jQuery, Drupal);
