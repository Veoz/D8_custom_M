(($, Drupal) => {
  /**
   * Sets the state of ERL field to loading and adds loading indicator to element.
   * @param {jQuery} $element The jQuery object to set loading state for.
   */
  function setLoading($element) {
    $element
      .addClass("erl-loading")
      .prepend(
        '<div class="loading"><div class="spinner">Loading...</div></div>'
      )
      .closest(".erl-field")
      .data("isLoading", true);
  }
  /**
   * Sets the state of ERL field to loaded and removes loading indicator.
   * @param {jQuery} $erlField The jQuery object to set loading state for.
   */
  function setLoaded($erlField) {
    $erlField
      .data("isLoading", false)
      .find(".erl-loading")
      .removeClass("erl-loading")
      .find(".loading")
      .remove();
  }
  /**
   * Returns true if the erlField is loading (i.e. waiting for an Ajax response.)
   * @param {jQuery} $erlField The ERL jQuery DOM object.
   * @return {bool} True if state is loading.
   */
  function isLoading($erlField) {
    return $erlField.data("isLoading");
  }
  /**
   * Ajax Command to set state to loaded.
   * @param {object} ajax The ajax object.
   * @param {object} response The response object.
   */
  Drupal.AjaxCommands.prototype.resetErlState = (ajax, response) => {
    setLoaded($(response.data.id));
  };
  /**
   * The main ERL Widget behavior.
   */
  Drupal.behaviors.erlWidget = {
    attach: function attach(context) {
      /**
       * Returns the region name closes to $el.
       * @param {jQuery} $el The jQuery element.
       * @return {string} The name of the region.
       */
      function getRegion($el) {
        const regEx = /erl-layout-region--([a-z0-9A-Z_]*)/;
        const $container = $el.is(".erl-layout-region")
          ? $el
          : $el.parents(".erl-layout-region");
        let regionName;
        if ($container.length) {
          const matches = $container[0].className.match(regEx);
          if (matches && matches.length >= 2) {
            [, regionName] = matches;
          }
        }
        return regionName;
      }
      /**
       * Updates all field weights and region names based on current state of dom.
       * @param {jQuery} $container The jQuery ERL Field container.
       */
      function updateFields($container) {
        // Set deltas:
        let delta = -1;
        $container
          .find(".erl-weight, .erl-new-item-delta")
          .each((index, item) => {
            if ($(item).hasClass("erl-weight")) {
              delta += 1;
            }
            $(item).val(`${delta}`);
          });
        $container.find("input.erl-region").each((index, item) => {
          $(item).val(getRegion($(item)));
        });
      }
      /**
       * Hides the disabled container when there are no ERL items.
       * @param {jQuery} $container The disabled items jQuery container.
       */
      function updateDisabled($container) {
        if ($container.find(".erl-disabled-items .erl-item").length > 0) {
          $container.find(".erl-disabled-items__description").hide();
        } else {
          $container.find(".erl-disabled-items__description").show();
        }
      }
      /**
       * Moves an ERL item up.
       * @param {event} e DOM Event (i.e. click).
       * @return {bool} Returns false if state is still loading.
       */
      function moveUp(e) {
        const $btn = $(e.currentTarget);
        const $item = $btn.parents(".erl-item:first");
        const $container = $item.parent();

        if (isLoading($item)) {
          return false;
        }

        // We're first, jump up to next available region.
        if ($item.prev(".erl-item").length === 0) {
          // Previous region, same layout.
          if ($container.prev(".erl-layout-region").length) {
            $container.prev(".erl-layout-region").append($item);
          }
          // Otherwise jump to last region in previous layout.
          else if (
            $container
              .closest(".erl-layout")
              .prev()
              .find(".erl-layout-region:last-child").length
          ) {
            $container
              .closest(".erl-layout")
              .prev()
              .find(".erl-layout-region:last-child .erl-add-content__container")
              .before($item);
          }
        } else {
          $item.after($item.prev());
        }
        updateFields($container.closest(".erl-field"));
      }
      /**
       * Moves an ERL item down.
       * @param {event} e DOM Event (i.e. click).
       * @return {bool} Returns false if state is still loading.
       */
      function moveDown(e) {
        const $btn = $(e.currentTarget);
        const $item = $btn.parents(".erl-item:first");
        const $container = $item.parent();

        if (isLoading($item)) {
          return false;
        }

        // We're first, jump down to next available region.
        if ($item.next(".erl-item").length === 0) {
          // Next region, same layout.
          if ($container.next(".erl-layout-region").length) {
            $container.next(".erl-layout-region").prepend($item);
          }
          // Otherwise jump to first region in next layout.
          else if (
            $container
              .closest(".erl-layout")
              .next()
              .find(".erl-layout-region:first-child").length
          ) {
            $container
              .closest(".erl-layout")
              .next()
              .find(
                ".erl-layout-region:first-child .erl-add-content__container"
              )
              .before($item);
          }
        } else {
          $item.before($item.next());
        }
        updateFields($container.closest(".erl-field"));
      }
      /**
       * Initiates dragula drag/drop functionality.
       * @param {object} item ERL field item to attach drag/drop behavior to.
       */
      function dragulaBehaviors(item) {
        $(item).addClass("dragula-enabled");

        // Turn on drag and drop if dragula function exists.
        if (typeof dragula !== "undefined") {

          // Add layout handles.
          $(".erl-item", item).each((erlItemIndex, erlItem) => {
            $('<div class="layout-controls">')
              .append($('<div class="layout-handle">'))
              .append($('<div class="layout-up">').click(moveUp))
              .append($('<div class="layout-down">').click(moveDown))
              .prependTo(erlItem);
          });
          const drake = dragula(
            $(
              ".erl-layout-wrapper, .erl-layout-region, .erl-disabled-wrapper",
              item
            ).get(),
            {
              moves(el, container, handle) {
                return (
                  handle.className.toString().indexOf("layout-handle") >= 0
                );
              },

              accepts(el, target, source, sibling) {
                const $el = $(el);

                // Regions always have to have a sibling,
                // forcing layout controls to be last element in container.
                if (!$el.is(".erl-layout") && !sibling) {
                  return false;
                }

                // Layouts can never go inside another layout.
                if ($el.is(".erl-layout")) {
                  if ($(target).parents(".erl-layout").length) {
                    return false;
                  }
                }

                // Layouts can not be dropped into disabled (only individual items).
                if ($el.is(".erl-layout")) {
                  if ($(target).is(".erl-disabled-wrapper")) {
                    return false;
                  }
                }
                // Require non-layout items to be dropped in a layout.
                else if (
                  $(target).parents(".erl-layout").length === 0 &&
                  !$(target).is(".erl-disabled-wrapper")
                ) {
                  return false;
                }

                return true;
              }
            }
          );

          drake.on("drop", el => {
            updateFields($(el).closest(".erl-field"));
            updateDisabled($(el).closest(".erl-field"));
          });
        }
      }
      /**
       * Closes the "add paragraph item" menu.
       * @param {jQuery} $btn The clicked button.
       */
      function closeAddItemMenu($btn) {
        const $widget = $btn.parents(".erl-field");
        const $menu = $widget.find(".erl-add-more-menu");
        $menu.addClass("hidden").removeClass("fade-in");
        $btn.removeClass("active");
      }
      /**
       * Responds to click outside of the menu.
       * @param {event} e DOM event (i.e. click)
       */
      function handleClickOutsideMenu(e) {
        if ($(e.target).closest(".erl-add-more-menu").length === 0) {
          const $btn = $(".erl-add-content__toggle.active");
          if ($btn.length) {
            closeAddItemMenu($btn);
            window.removeEventListener("click", handleClickOutsideMenu);
          }
        }
      }
      /**
       * Position the menu correctly.
       * @param {jQuery} $menu The menu jQuery DOM object.
       * @param {bool} keepOrientation If true, the menu will stay above/below no matter what.
       */
      function positionMenu($menu, keepOrientation) {
        const $btn = $menu.data("activeButton");
        // Move the menu to correct spot.
        const btnOffset = $btn.offset();
        const menuOffset = $menu.offset();
        const viewportTop = $(window).scrollTop();
        const viewportBottom = viewportTop + $(window).height();
        const menuWidth = $menu.outerWidth();
        const btnWidth = $btn.outerWidth();
        const btnHeight = $btn.height();
        const menuHeight = $menu.outerHeight();
        // Account for rotation with slight padding.
        const left =
          7 + Math.floor(btnOffset.left + btnWidth / 2 - menuWidth / 2);

        // Default to positioning the menu beneath the button.
        let orientation = "beneath";
        let top = Math.floor(btnOffset.top + btnHeight + 15);

        // The menu is above the button, keep it that way.
        if (keepOrientation === true && menuOffset.top < btnOffset.top) {
          orientation = "above";
        }
        // The menu would go out of the viewport, so keep at top.
        if (top + menuHeight > viewportBottom) {
          orientation = "above";
        }
        $menu
          .removeClass("above")
          .removeClass("beneath")
          .addClass(orientation);
        if (orientation === "above") {
          top = Math.floor(btnOffset.top - 5 - menuHeight);
        }

        $menu.removeClass("hidden").addClass("fade-in");
        $menu.offset({ top, left });
      }
      /**
       * Opens the "add paragraph item" menu.
       * @param {jQuery} $btn The button clicked to open the menu.
       */
      function openAddItemMenu($btn) {
        const $widget = $btn.parents(".erl-field");
        const $regionInput = $widget.find(".erl-new-item-region");
        const $parentInput = $widget.find(".erl-new-item-parent");
        const $menu = $widget.find(".erl-add-more-menu");
        const region = getRegion($btn.closest(".erl-layout-region"));
        const parent = $btn
          .closest(".erl-layout")
          .find(".erl-weight")
          .val();
        $menu.data("activeButton", $btn);
        // Make other buttons inactive.
        $widget.find("button.erl-add-content__toggle").removeClass("active");
        // Hide the menu, for transition effect.
        $menu.addClass("hidden").removeClass("fade-in");
        $menu.find('input[type="text"]').val("");
        $menu.find(".erl-add-more-menu__item").attr("style", "");
        $btn.addClass("active");

        // Sets the values in the form items
        // for where a new item should be inserted.
        $regionInput.val(region);
        $parentInput.val(parent);
        setTimeout(() => {
          positionMenu($menu);
        }, 200);
        if (!$menu.find(".erl-add-more-menu__search").hasClass("hidden")) {
          $menu.find('.erl-add-more-menu__search input[type="text"]').focus();
        }
        window.addEventListener("click", handleClickOutsideMenu);
      }
      /**
       * Enhances the radio button select for choosing a layout.
       */
      function enhanceRadioSelect() {
        const $layoutRadioItem = $(".layout-radio-item");
        $layoutRadioItem.click(e => {
          const $radioItem = $(e.currentTarget);
          const $erlField = $radioItem.closest(".erl-field");
          if (isLoading($erlField)) {
            return false;
          }
          setLoading($radioItem.closest(".ui-dialog"));
          $radioItem
            .find("input[type=radio]")
            .prop("checked", true)
            .trigger("change");
          $radioItem.siblings().removeClass("active");
          $radioItem.addClass("active");
        });
        $layoutRadioItem.each((radioIndex, radioItem) => {
          const $radioItem = $(radioItem);
          if ($radioItem.find("input[type=radio]").prop("checked")) {
            $radioItem.addClass("active");
          }
        });
      }
      /**
       * Set state to "loading" on ERL field when action buttons are press.
       */
      $('.erl-actions input[type="submit"]')
        .once("erl-actions-loaders")
        .each((index, btn) => {
          $(btn).on("mousedown", e => {
            if (isLoading($(btn).closest(".erl-field"))) {
              e.stopImmediatePropagation();
              return false;
            }
            setLoading($(e.currentTarget).closest(".erl-item"));
          });
          // Ensure our listener happens first.
          $._data(btn, "events").mousedown.reverse();
        });
      /**
       * Click handler for "add paragraph item" toggle buttons.
       */
      $("button.erl-add-content__toggle")
        .once("erl-add-content-toggle")
        .click(e => {
          const $btn = $(e.target);
          if ($btn.hasClass("active")) {
            closeAddItemMenu($btn);
          } else {
            openAddItemMenu($btn);
          }
          return false;
        });
      /**
       * Click handlers for adding new paragraph items.
       */
      $(".erl-add-more-menu__item a", context)
        .once("erl-add-more-menu-buttons")
        .click(e => {
          const $btn = $(e.currentTarget);
          const $widget = $btn.closest(".erl-field");
          const $menu = $btn.closest(".erl-add-more-menu");
          const $select = $widget.find("select.erl-item-type");
          const $submit = $widget.find('input[type="submit"].erl-add-item');
          const type = $btn.attr("data-type");
          if (isLoading($widget)) {
            return false;
          }
          $select.val(type);
          $submit.trigger("mousedown").trigger("click");
          setLoading($menu);
          return false;
        });
      /**
       * Search behavior for search box on "add paragraph item" menu.
       */
      $(".erl-add-more-menu__search", context)
        .once("erl-search-input")
        .each((index, searchContainer) => {
          const $searchContainer = $(searchContainer);
          const $searchInput = $searchContainer.find('input[type="text"]');
          const $menu = $searchContainer.closest(".erl-add-more-menu");
          const $searchItems = $menu.find(".erl-add-more-menu__item");

          // Do nothing if there are only 6 or fewer items.
          if ($searchItems.length <= 6) {
            return;
          }
          $searchContainer.removeClass("hidden");
          // Search query
          $searchInput.on("keyup", ev => {
            const text = ev.target.value;
            const pattern = new RegExp(text, "i");
            for (let i = 0; i < $searchItems.length; i++) {
              const item = $searchItems[i];
              if (pattern.test(item.innerText)) {
                item.removeAttribute("style");
              } else {
                item.style.display = "none";
              }
            }
            positionMenu($menu, true);
          });
        });
      /**
       * Click handlers for "Add New Section" buttons.
       */
      $(".erl-field", context)
        .once("erl-add-section")
        .each((index, erlField) => {
          const $widgetContainer = $(erlField);
          const $submitButton = $widgetContainer.find("input.erl-add-section");
          const $regionInput = $widgetContainer.find(".erl-new-item-region");
          const $parentInput = $widgetContainer.find(".erl-new-item-parent");

          $("button.erl-add-section", erlField).click(e => {
            if (isLoading($widgetContainer)) {
              return false;
            }
            const $btn = $(e.currentTarget);
            const parent = $btn
              .closest(".erl-layout")
              .find(".erl-weight")
              .val();
            $parentInput.val(parent);
            // Sections don't go in regions.
            $regionInput.val("");
            $submitButton.trigger("mousedown").trigger("click");
            setLoading($btn.parent());
            return false;
          });
        });
      /**
       * Load entity form in dialog.
       */
      $(".erl-field .erl-form", context)
        .once("erl-dialog")
        .each((index, erlForm) => {
          const buttons = [];
          const $erlForm = $(erlForm);
          $('.erl-item-form-actions input[type="submit"]', erlForm).each(
            (btnIndex, btn) => {
              buttons.push({
                text: btn.value,
                class: btn.className,
                click() {
                  if (isLoading($erlForm.closest(".erl-field"))) {
                    return false;
                  }
                  setLoading($erlForm.closest(".ui-dialog"));
                  $(btn)
                    .trigger("mousedown")
                    .trigger("click");
                }
              });
              btn.style.display = "none";
            }
          );
          const dialogConfig = {
            width: "800px",
            title: $erlForm
              .find("[data-dialog-title]")
              .attr("data-dialog-title"),
            maxHeight: Math.max(400, $(window).height() * 0.8),
            minHeight: Math.min($erlForm.outerHeight(), 400),
            appendTo: $(".erl-form").parent(),
            draggable: true,
            autoResize: true,
            modal: true,
            buttons,
            open() {
              enhanceRadioSelect();
            },
            beforeClose(event) {
              if (isLoading($(event.target).closest(".erl-field"))) {
                return false;
              }
              setLoading($(event.target).closest(".ui-dialog"));
              $(event.target)
                .find(".erl-cancel")
                .trigger("mousedown")
                .trigger("click");
              return false;
            }
          };
          $erlForm.dialog(dialogConfig);
        });
      /**
       * Drag and drop with dragula.
       */
      $(".erl-field", context)
        .once("erl-drag-drop")
        .each((index, item) => {
          const checkDragulaInterval = setInterval(function(){
            if (typeof dragula !== "undefined") {
              clearInterval(checkDragulaInterval);
              dragulaBehaviors(item);
            }
          }, 50);
        });
      /**
       * Update weights, regions, and disabled area on load.
       */
      $(".erl-field", context)
        .once("erl-update-fields")
        .each((index, item) => {
          updateFields($(item));
          updateDisabled($(item));
        });
    }
  };
})(jQuery, Drupal);
