// RFTG main javascript
define([
        "dojo", "dojo/_base/declare",
        "ebg/core/gamegui",
        "dijit/Dialog",
        "dijit/Tooltip",
        "ebg/scrollmap",
        "ebg/counter",
        "ebg/stock",
        "ebg/draggable"
    ],
    function(dojo, declare) {
        return declare("bgagame.raceforthegalaxy", ebg.core.gamegui, {
            constructor: function() {
                console.log('raceforthegalaxy constructor');

                this.dontPreloadImage('cards_NW.jpg');

                this.dontPreloadImage('goals.jpg');
                this.dontPreloadImage('cards_TGS.jpg');
                this.dontPreloadImage('cards_RvI.jpg');
                this.dontPreloadImage('cards_BoW.jpg');

                this.dontPreloadImage('cards_AA.jpg');
                this.dontPreloadImage('artefacts.png');
                this.dontPreloadImage('orb.jpg');

                this.dontPreloadImage('cards_XI.jpg');
                this.dontPreloadImage('xeno_repulse_track.jpg');

                this.card_size = null;
                this.tooltip_card_size = null;
                this.tooltip_delay = 0;
                // Mobile long-press behavior tuning.
                this.tooltip_long_press_delay = 450;
                this.tooltip_long_press_click_suppression = 350;

                this.playerHand = null;
                this.playerHandOrb = null;
                this.playerHandArt = null;
                this.exploreSet = null;
                this.scavengerSet = null;

                this.paymentMode = false; // During develop and settle phase, beeing in "payment mode" means: choice cards to pay nextCardToPlay
                this.nextCardToPlay = null;
                this.paymentCost = 0;
                this.immediateAlternatives = null; // Lists actions during develop and settle if cost is zero but the player must make a choice
                this.isMilitarySettle = false; // purely military settle action (relevant for confirmation)
                this.card_to_type = {}; // Card_id => type_id (at least for cards in tableau)

                this.goals = null;
                this.pgoals = {};
                this.deck = null;

                this.current_orb_card_id = null;
                this.teamPlace = {};
                this.playerPlayedArt = {};
                this.prestige_action = false;
                this.phases_chosen = 0;
                this.current_phase_choices = null;
                this.pending_phase_choice = {};
                this.tooltips = {};
                this.tooltipsInfos = {};
                // Tracks the currently-open long-press tooltip (if any).
                this.currentLongPressTooltip = null;
                // Time window used to swallow synthetic click events after long-press.
                this.longPressSuppressUntil = 0;
                this.masterTooltipCleanupObserver = null;

            },

            isTouchInterface: function() {
                return dojo.hasClass('ebd-body', 'touch-device');
            },
            consumeEvent: function(evt) {
                evt.preventDefault();
                evt.stopPropagation();
                if (evt.stopImmediatePropagation) {
                    evt.stopImmediatePropagation();
                }
            },
            isSmallTouchscreen: function() {
                return this.isTouchInterface() && window.visualViewport.width <= 800;
            },
            setLongPressTooltipVisibility: function(visible) {
                if (!this.isTouchInterface()) {
                    return;
                }
                if (visible && this.isSmallTouchscreen()) {
                    dojo.addClass($('ebd-body'), 'rftg-mobile-tooltip-overlay-visible');
                } else {
                    dojo.removeClass($('ebd-body'), 'rftg-mobile-tooltip-overlay-visible');
                }
            },
            cancelMasterTooltipLayoutCleanup: function() {
                if (this.masterTooltipCleanupObserver !== null) {
                    this.masterTooltipCleanupObserver.disconnect();
                    this.masterTooltipCleanupObserver = null;
                }
            },
            updateMasterTooltipLayout: function() {
                var tooltipNode = $('dijit__MasterTooltip_0');
                if (!tooltipNode) {
                    this.setLongPressTooltipVisibility(false);
                    return;
                }
                // Clear the temporary "closing" style before opening/repositioning.
                dojo.removeClass(tooltipNode, 'rftg-mobile-tooltip-closing');
                // On small touch screens, center tooltips; otherwise keep the default anchored layout.
                if (this.isSmallTouchscreen()) {
                    dojo.addClass(tooltipNode, 'rftg-mobile-tooltip-centered');
                }
                this.setLongPressTooltipVisibility(true);
            },
            scheduleMasterTooltipLayoutCleanup: function() {
                this.cancelMasterTooltipLayoutCleanup();
                var cleanup = dojo.hitch(this, function() {
                    if (this.currentLongPressTooltip) {
                        return;
                    }
                    var tooltipNode = $('dijit__MasterTooltip_0');
                    if (tooltipNode) {
                        dojo.removeClass(tooltipNode, 'rftg-mobile-tooltip-centered');
                        dojo.removeClass(tooltipNode, 'rftg-mobile-tooltip-closing');
                    }
                    this.setLongPressTooltipVisibility(false);
                    this.cancelMasterTooltipLayoutCleanup();
                });

                var tooltipNode = $('dijit__MasterTooltip_0');
                if (!tooltipNode) {
                    cleanup();
                    return;
                }

                this.masterTooltipCleanupObserver = new MutationObserver(function() {
                    var node = $('dijit__MasterTooltip_0');
                    if (!node) {
                        cleanup();
                        return;
                    }
                    if (dojo.hasClass(node, 'dijitTooltipHidden')) {
                        cleanup();
                    }
                });
                this.masterTooltipCleanupObserver.observe(tooltipNode, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            },
            closeCurrentLongPressTooltip: function() {
                // Remove the overlay immediately so dismiss feels responsive.
                this.setLongPressTooltipVisibility(false);
                if (!this.currentLongPressTooltip) {
                    return;
                }
                var tooltipNode = $('dijit__MasterTooltip_0');
                if (tooltipNode) {
                    // Hide the master tooltip node during close to avoid visual flicker.
                    dojo.addClass(tooltipNode, 'rftg-mobile-tooltip-closing');
                }
                this.currentLongPressTooltip.tooltip.close(this.currentLongPressTooltip.target);
                this.currentLongPressTooltip = null;
                this.scheduleMasterTooltipLayoutCleanup();
            },
            findLongPressTarget: function(container, startNode, selector) {
                if (!selector) {
                    return container;
                }
                // Support delegated targets like ".breeding_tube" inside a container.
                var node = startNode;
                if (node && node.nodeType !== 1) {
                    node = node.parentElement;
                }
                if (!node || !node.closest) {
                    return null;
                }
                var target = node.closest(selector);
                if (!target || !container.contains(target)) {
                    return null;
                }
                return target;
            },
            registerLongPressTooltip: function(container, tooltip, selector) {
                if (!this.isTouchInterface() || !container || !tooltip) {
                    return;
                }

                var handlerKey = selector || '__self';
                if (!container._rftgLongPressHandlers) {
                    container._rftgLongPressHandlers = {};
                }
                var oldState = container._rftgLongPressHandlers[handlerKey];
                if (oldState) {
                    if (oldState.timer !== null) {
                        clearTimeout(oldState.timer);
                    }
                    container.removeEventListener('touchstart', oldState.onTouchStart, false);
                    container.removeEventListener('touchmove', oldState.onTouchMove, false);
                    container.removeEventListener('touchend', oldState.onTouchEnd, false);
                    container.removeEventListener('touchcancel', oldState.onTouchCancel, false);
                    container.removeEventListener('contextmenu', oldState.onContextMenu, true);
                }

                var state = {
                    timer: null,
                    target: null,
                    // True after the long-press timer fired and tooltip opened.
                    longPressTriggered: false
                };
                var clearTimer = function() {
                    if (state.timer !== null) {
                        clearTimeout(state.timer);
                        state.timer = null;
                    }
                };

                state.onTouchStart = dojo.hitch(this, function(evt) {
                    state.target = this.findLongPressTarget(container, evt.target, selector);
                    if (!state.target) {
                        return;
                    }
                    state.longPressTriggered = false;
                    clearTimer();
                    state.timer = setTimeout(dojo.hitch(this, function() {
                        state.timer = null;
                        // Only one long-press tooltip should be visible at a time.
                        this.closeCurrentLongPressTooltip();
                        this.cancelMasterTooltipLayoutCleanup();
                        tooltip.open(state.target);
                        this.updateMasterTooltipLayout();
                        this.longPressSuppressUntil = Date.now() + this.tooltip_long_press_click_suppression;
                        state.longPressTriggered = true;
                        this.currentLongPressTooltip = {
                            tooltip: tooltip,
                            target: state.target
                        };
                    }), this.tooltip_long_press_delay);
                });
                state.onTouchMove = function() {
                    // Finger movement cancels pending long-press.
                    state.target = null;
                    clearTimer();
                };
                state.onTouchEnd = dojo.hitch(this, function(evt) {
                    if (state.longPressTriggered) {
                        // Long-press should not also count as a normal tap/click.
                        this.longPressSuppressUntil = Date.now() + this.tooltip_long_press_click_suppression;
                        this.consumeEvent(evt);
                    }
                    state.longPressTriggered = false;
                    state.target = null;
                    clearTimer();
                });
                state.onTouchCancel = function() {
                    state.longPressTriggered = false;
                    state.target = null;
                    clearTimer();
                };
                state.onContextMenu = dojo.hitch(this, function(evt) {
                    // Suppress browser context menu on elements with long-press tooltip.
                    var target = this.findLongPressTarget(container, evt.target, selector);
                    if (target) {
                        this.consumeEvent(evt);
                    }
                });

                container.addEventListener('touchstart', state.onTouchStart, { passive: false });
                container.addEventListener('touchmove', state.onTouchMove, { passive: false });
                container.addEventListener('touchend', state.onTouchEnd, { passive: false });
                container.addEventListener('touchcancel', state.onTouchCancel, false);
                container.addEventListener('contextmenu', state.onContextMenu, true);
                container._rftgLongPressHandlers[handlerKey] = state;
            },
            createManagedTooltipForNode: function(node, contentProvider, showDelay) {
                if (!node) {
                    return null;
                }
                var tooltip = null;
                if (this.isTouchInterface()) {
                    // On touch we open tooltips manually via long-press handlers.
                    tooltip = new dijit.Tooltip({
                        position: this.defaultTooltipPosition,
                        getContent: function() {
                            return (typeof contentProvider === 'function') ? contentProvider(node) : contentProvider;
                        }
                    });
                    this.registerLongPressTooltip(node, tooltip);
                } else {
                    // On desktop, keep the standard hover-driven Dojo tooltip behavior.
                    tooltip = new dijit.Tooltip({
                        connectId: [node.id],
                        position: this.defaultTooltipPosition,
                        showDelay: showDelay,
                        getContent: function() {
                            return (typeof contentProvider === 'function') ? contentProvider(node) : contentProvider;
                        }
                    });
                    dojo.connect(node, 'onclick', tooltip, 'close');
                }
                return tooltip;
            },
            attachDesktopTooltipHoverClose: function(id, tooltip) {
                if (this.isTouchInterface() || !tooltip) {
                    return;
                }
                // Copied from addTooltipHtml() in ly_studio.js
                this.tooltipsInfos[id] = {
                  hideOnHoverEvt: null
                };
                dojo.connect(tooltip, '_onHover', dojo.hitch(this, function () {
                  if ((this.tooltipsInfos[id].hideOnHoverEvt === null) && $('dijit__MasterTooltip_0')) {
                    this.tooltipsInfos[id].hideOnHoverEvt = dojo.connect($('dijit__MasterTooltip_0'), 'onmouseenter', tooltip, 'close');
                  }
                }));
            },
            addTooltip: function(nodeId, helpStringTranslated, actionStringTranslated, delay) {
                if (!this.isTouchInterface()) {
                    this.inherited(arguments);
                    return;
                }
                var html = '<div class="midSizeDialog">' + helpStringTranslated;
                if (actionStringTranslated !== '') {
                    html += '<hr/>' + actionStringTranslated;
                }
                html += '</div>';
                this.addTooltipHtml(nodeId, html, delay);
            },
            addTooltipHtml: function(nodeId, html, delay) {
                if (!this.isTouchInterface()) {
                    this.inherited(arguments);
                    return;
                }
                var node = $(nodeId);
                if (!node) {
                    return;
                }
                if (this.tooltips[nodeId]) {
                    this.tooltips[nodeId].destroy();
                }
                var tooltipDelay = (typeof delay === 'undefined') ? this.tooltip_delay : delay;
                this.tooltips[nodeId] = this.createManagedTooltipForNode(node, function() {
                    return html;
                }, tooltipDelay);
            },
            addTooltipToClass: function(cssclass, helpStringTranslated, actionStringTranslated, delay) {
                if (!this.isTouchInterface()) {
                    this.inherited(arguments);
                    return;
                }
                dojo.query('.' + cssclass).forEach(dojo.hitch(this, function(node) {
                    // Generate an ID for the node if it doesn't have one.
                    // `this.inherited()` does this on desktop, but we have to
                    // do it ourselves here.
                    if (!node.id) {
                        node.id = dojox.uuid.generateRandomUuid();
                    }

                    this.addTooltip(node.id, helpStringTranslated, actionStringTranslated, delay);
                }));
            },

            setup: function(gamedatas) {
                this.initPreferences();

                if (this.isTouchInterface()) {
                    document.addEventListener('click', dojo.hitch(this, function(evt) {
                        if (Date.now() < this.longPressSuppressUntil) {
                            this.consumeEvent(evt);
                        }

                        // If a modal tooltip is open, dismiss it if you click
                        // outside of it.
                        if (this.currentLongPressTooltip) {
                            var tooltipNode = $('dijit__MasterTooltip_0');
                            // Clicks inside the tooltip should not dismiss it.
                            if (tooltipNode && tooltipNode.contains(evt.target)) {
                                return;
                            }
                            this.longPressSuppressUntil = Date.now() + this.tooltip_long_press_click_suppression;
                            this.closeCurrentLongPressTooltip();
                            // Consume this click event so that any underlying
                            // game element is not activated.
                            this.consumeEvent(evt);
                        }
                    }), true);

                    // Disable native long-press context menu on touch devices.
                    document.addEventListener('contextmenu', dojo.hitch(this, function(evt) {
                        this.consumeEvent(evt);
                    }), true);
                }

                console.log("start creating player boards");
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];
                    var player_board_div = this.bga.playerPanels.getElement(player_id);
                    dojo.place(this.format_block('jstpl_player_board', {
                        id: player.id
                    }), player_board_div);

                    // Set the board title color to the player color
                    var color = player.color;
                    // Special case for black
                    if (color == '000000') {
                        color = 'ffffff';
                    }
                    $('tableau_title_' + player_id).style.color = `#${color}`;

                    if (gamedatas.hand_count[player_id]) {
                        $('card_hand_nbr_' + player_id).innerHTML = gamedatas.hand_count[player_id];
                    } else {
                        $('card_hand_nbr_' + player_id).innerHTML = '0';
                    }
                    $('tableau_nbr_' + player_id).innerHTML = 0;
                    $('vp_nbr_' + player_id).innerHTML = player.vp;
                    if (typeof player.prestige != 'undefined') {
                        $('prestige_nbr_' + player_id).innerHTML = player.prestige;
                    } else {
                        dojo.style('prestige_nbr_' + player_id, 'display', 'none');
                    }
                    if (typeof player.prestige_search != 'undefined' && player.prestige_search > 0) {
                        dojo.style('prestige_search_' + player_id, 'visibility', 'visible');
                    }
                    $('milforce_' + player_id).innerHTML = player.milforce;

                    $('xenomilforce_' + player_id).innerHTML = player.xeno_milforce;
                    if (this.gamedatas.xeno.current_wave > 0) {
                        if (typeof player.xeno_milforce_tiebreak == 'undefined') {
                            $('xenotiebreaker_' + player_id).innerHTML = '';
                        } else {
                            $('xenotiebreaker_' + player_id).innerHTML = '(' + player.xeno_milforce_tiebreak + ')';
                        }
                    }
                    $('effortcount_' + player_id).innerHTML = player.effort;
                    $(`defenseaward_${player_id}`).innerHTML = player.defense_award;

                    this.pgoals[player_id] = new ebg.stock();
                    this.pgoals[player_id].create(this, $('goals_' + player_id), 56, 80);

                    this.pgoals[player_id].image_items_per_row = 1;
                    this.pgoals[player_id].apparenceBorderWidth = '2px';
                    this.pgoals[player_id].selectionApparance = 'class';
                    this.pgoals[player_id].setSelectionMode(0);
                    this.pgoals[player_id].onItemCreate = dojo.hitch(this, 'setupNewGoal');
                    this.pgoals[player_id].autowidth = true;
                }

                this.playerHand = new ebg.stock();
                this.playerHand.create(this, $('player_hand'), this.card_size['w'], this.card_size['h']);

                this.playerHand.image_items_per_row = 10;
                this.playerHand.image_in_vertical_row = true;
                this.playerHand.apparenceBorderWidth = '2px';
                this.playerHand.selectionApparance = 'class';
                this.playerHand.autowidth = true;
                this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
                dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

                if (this.isSpectator) {
                    dojo.style('hand_panel', 'display', 'none');
                    dojo.style('tableau_panel_' + this.player_id, 'display', 'none');
                } else {
                    if (this.checkAction("initialdiscard", true)) {
                        dojo.addClass('hand_panel', 'paymentMode');
                    }
                    if (this.checkAction("initialdiscardhome", true)) {
                        dojo.addClass('tableau_' + this.player_id, 'paymentMode');
                    }
                }

                if (this.gamedatas.expansion > 1) {
                    dojo.style('play_with_expansions', 'display', 'none');
                }

                if (this.gamedatas.expansion == 5) {
                    // Orb hand cards
                    this.playerHandOrb = new ebg.stock();
                    this.playerHandOrb.create(this, $('player_hand_orb'), 100, 140);

                    this.playerHandOrb.image_items_per_row = 10;
                    this.playerHandOrb.image_in_vertical_row = true;
                    this.playerHandOrb.apparenceBorderWidth = '2px';
                    this.playerHandOrb.selectionApparance = 'class';
                    this.playerHandOrb.selectionClass = 'selectedOrbCard';
                    this.playerHandOrb.autowidth = true;
                    this.playerHandOrb.setSelectionMode(1);
                    this.playerHandOrb.onItemCreate = dojo.hitch(this, 'setupNewOrbCard');
                    dojo.connect(this.playerHandOrb, 'onChangeSelection', this, 'onPlayerHandOrbSelectionChanged');
                    for (var orb_card_type_id in this.gamedatas.orb_cards_types) {
                        this.playerHandOrb.addItemType(orb_card_type_id, orb_card_type_id, g_gamethemeurl + 'img/orb.jpg', orb_card_type_id - 1);
                    }
                    for (var i in this.gamedatas.orbhand) {
                        var card = this.gamedatas.orbhand[i];
                        this.playerHandOrb.addToStockWithId(card.type, card.id);
                    }

                    if (!this.isSpectator) {
                        dojo.style('orb_hand_panel', 'display', 'block');
                    }
                    dojo.style('orb_wrap', 'display', 'block');

                    this.orb = new ebg.scrollmap();
                    this.orb.create($('alien_orb'), $('map_scrollable'), $('map_surface'), $('map_scrollable_oversurface'));
                    this.orb.setupOnScreenArrows(150);

                    for (var i in this.gamedatas.orbcards) {
                        var card = this.gamedatas.orbcards[i];

                        this.addCardToOrb(card);
                    }

                    this.extendOrb(this.gamedatas.orb);

                    this.setRemainingOrb(this.gamedatas.orb_deck);

                    this.addTooltip('priority_track', _("Orb priority track"), '');

                    for (var id in this.gamedatas.teams) {
                        this.setTeam(this.gamedatas.teams[id]);
                    }

                    for (var pid in this.gamedatas.priority) {
                        if (this.gamedatas.priority[pid] != 0 && this.gamedatas.priority[pid] !== null) {
                            this.slideToObject('priority_player_' + pid, 'priority_' + this.gamedatas.priority[pid]).play();
                        }
                    }

                    // Player hand artefacts
                    this.playerHandArt = new ebg.stock();
                    this.playerHandArt.create(this, $('player_hand_art'), 76, 67);

                    this.playerHandArt.image_items_per_row = 8;
                    this.playerHandArt.apparenceBorderWidth = '2px';
                    this.playerHandArt.selectionApparance = 'class';
                    this.playerHandArt.autowidth = true;
                    this.playerHandArt.onItemCreate = dojo.hitch(this, 'setupNewArtCard');
                    dojo.connect(this.playerHandArt, 'onChangeSelection', this, 'onPlayerHandArtSelectionChanged');
                    for (var art_card_type_id in this.gamedatas.artefact_types) {
                        this.playerHandArt.addItemType(art_card_type_id, art_card_type_id, g_gamethemeurl + 'img/artefacts.png', art_card_type_id - 1);
                    }
                    for (var i in this.gamedatas.arthand) {
                        var card = this.gamedatas.arthand[i];
                        this.playerHandArt.addToStockWithId(card.type, card.id);
                    }

                    for (var player_id in gamedatas.players) {
                        this.playerPlayedArt[player_id] = new ebg.stock();
                        this.playerPlayedArt[player_id].create(this, $('player_played_art_' + player_id), 76, 67);

                        this.playerPlayedArt[player_id].image_items_per_row = 8;
                        this.playerPlayedArt[player_id].apparenceBorderWidth = '2px';
                        this.playerPlayedArt[player_id].selectionApparance = 'class';
                        this.playerPlayedArt[player_id].setOverlap(70, 0);
                        this.playerPlayedArt[player_id].autowidth = true;
                        this.playerPlayedArt[player_id].onItemCreate = dojo.hitch(this, 'setupNewArtCard');
                        for (var art_card_type_id in this.gamedatas.artefact_types) {
                            this.playerPlayedArt[player_id].addItemType(art_card_type_id, art_card_type_id, g_gamethemeurl + 'img/artefacts.png', art_card_type_id - 1);
                        }

                        $(`orb_card_a_count_${player_id}`).innerHTML = gamedatas.orbhand_count.a[player_id];
                        if (gamedatas.orbhand_count.b[player_id] > 0) {
                            $(`orb_card_b_count_${player_id}`).innerHTML = gamedatas.orbhand_count.b[player_id];
                        } else {
                            dojo.style(`orb_card_b_wrap_${player_id}`, 'display', 'none');
                        }
                        $(`artefact_A_count_${player_id}`).innerHTML = gamedatas.artefact_count.A[player_id];
                        if (gamedatas.artefact_count.B[player_id] > 0) {
                            $(`artefact_B_count_${player_id}`).innerHTML = gamedatas.artefact_count.B[player_id];
                        } else {
                            dojo.style(`artefact_B_wrap_${player_id}`, 'display', 'none');
                        }
                    }
                    for (var i in this.gamedatas.artplayed) {
                        var card = this.gamedatas.artplayed[i];
                        this.playerPlayedArt[card.location_arg].addToStockWithId(card.type, card.id);
                        if (this.playerPlayedArt[card.location_arg].count() > 10) {
                            this.playerPlayedArt[card.location_arg].setOverlap(50, 0);
                        }
                    }

                    this.addArtefactsToOrb(this.gamedatas.artefact);
                }

                this.deck = new ebg.stock();
                this.deck.create(this, $('my_deck'), this.card_size['w'], this.card_size['h']);

                this.deck.image_items_per_row = 10;
                this.deck.image_in_vertical_row = true;
                this.deck.apparenceBorderWidth = '2px';
                this.deck.selectionApparance = 'class';
                this.deck.setSelectionMode(0);
                this.deck.onItemCreate = dojo.hitch(this, 'setupNewCard');

                if (this.gamedatas.expansion == 3 || this.gamedatas.expansion == 4) {
                    if (this.gamedatas.takeovers) {
                        dojo.style('takeovers_off_img', 'display', 'none');
                        this.addTooltipHtml('takeovers_on_img', '<h3>' + _("Takeovers are allowed") + '</h3>', '');
                    } else {
                        dojo.style('takeovers_on_img', 'display', 'none');
                        this.addTooltipHtml('takeovers_off_img', '<h3>' + _("Takeovers are disabled") + '</h3>', '');
                    }
                } else {
                    dojo.style('takeover_toggle_warp', 'display', 'none');
                }


                this.goals = new ebg.stock();
                this.goals.create(this, $('goals'), 56, 80);

                this.goals.image_items_per_row = 1;
                this.goals.apparenceBorderWidth = '2px';
                this.goals.selectionApparance = 'class';
                this.goals.setSelectionMode(0);
                this.goals.onItemCreate = dojo.hitch(this, 'setupNewGoal');

                this.exploreSet = new ebg.stock();
                this.exploreSet.create(this, $('explore_set'), this.card_size['w'], this.card_size['h']);

                this.exploreSet.image_items_per_row = 10;
                this.exploreSet.image_in_vertical_row = true;
                this.exploreSet.apparenceBorderWidth = '2px';
                this.exploreSet.onItemCreate = dojo.hitch(this, 'setupNewCard');
                dojo.connect(this.exploreSet, 'onChangeSelection', this, 'onExploredSelectionChanged');

                if (this.gamedatas.expansion == 4) {
                    this.scavengerSet = new ebg.stock();
                    this.scavengerSet.create(this, $('scavenger_set'), this.card_size['w'], this.card_size['h']);

                    this.scavengerSet.image_items_per_row = 10;
                    this.scavengerSet.image_in_vertical_row = true;
                    this.scavengerSet.autowidth = true;
                    this.scavengerSet.onItemCreate = dojo.hitch(this, 'setupNewCard');
                }

                for (var card_type_id in gamedatas.card_types) {
                    var card_type = gamedatas.card_types[card_type_id];
                    var sort_order = card_type["type"] == "development" ? 10 : 0;
                    sort_order += card_type["cost"];

                    var card_back = this.cardBackground(card_type_id);
                    this.playerHand.addItemType(card_type_id, sort_order, card_back['background'],  card_back['card_type_id']);
                    this.exploreSet.addItemType(card_type_id, sort_order, card_back['background'], card_back['card_type_id']);
                    if (this.gamedatas.expansion == 4) {
                        this.scavengerSet.addItemType(card_type_id, sort_order, card_back['background'], card_back['card_type_id']);
                    }
                    this.deck.addItemType(card_type_id, sort_order, card_back['background'], card_back['card_type_id']);
                }

                for (var goal_id in gamedatas.goals.first) {
                    var goal = gamedatas.goals.first[goal_id];
                    this.goals.addItemType(goal.type, goal.type, g_gamethemeurl + 'img/goals.png', goal.type < 150 ? goal.type - 120 : (goal.type < 200 ? goal.type - 170 + 10 : goal.type - 221 + 15));
                    for (var player_id in gamedatas.players) {
                        this.pgoals[player_id].addItemType(goal.type, goal.type, g_gamethemeurl + 'img/goals.png', goal.type < 150 ? goal.type - 120 : (goal.type < 200 ? goal.type - 170 + 10 : goal.type - 221 + 15));
                    }
                    if (goal.location_arg == 0) {
                        this.goals.addToStockWithId(goal.type, goal_id);
                    } else {
                        this.pgoals[goal.location_arg].addToStockWithId(goal.type, goal_id);
                    }
                }
                for (var goal_id in gamedatas.goals.most) {
                    var goal = gamedatas.goals.most[goal_id];
                    this.goals.addItemType(goal.type, 1000 + goal.type, g_gamethemeurl + 'img/goals.png', goal.type < 150 ? goal.type - 120 : (goal.type < 200 ? goal.type - 170 + 10 : goal.type - 221 + 15));
                    for (var player_id in gamedatas.players) {
                        this.pgoals[player_id].addItemType(goal.type, 1000 + goal.type, g_gamethemeurl + 'img/goals.png', goal.type < 150 ? goal.type - 120 : (goal.type < 200 ? goal.type - 170 + 10 : goal.type - 221 + 15));
                    }
                    if (goal.location_arg == 0) {
                        this.goals.addToStockWithId(goal.type, goal_id);
                    } else {
                        this.pgoals[goal.location_arg].addToStockWithId(goal.type, goal_id);
                    }
                }

                if (!this.gamedatas.takeovers) {
                    dojo.query('.rebel_vulnerability').style('display', 'none');
                    dojo.query('.imperium_vulnerability').style('display', 'none');
                }
                if (this.gamedatas.expansion != 4) {
                    dojo.query('.prestigecount').style('display', 'none');
                    dojo.query('.prestige_search').style('display', 'none');
                }
                if (this.gamedatas.expansion != 5) {
                    dojo.query('.aa_block').style('display', 'none');
                }
                if (this.gamedatas.expansion == 7) {
                    dojo.addClass('ebd-body', 'rftg_xeno_invasion');

                    if (this.gamedatas.xeno.current_wave > 0) {
                        this.placeAdmiralDisks();
                    }

                    var coord = this.getXenoTrackCoord(this.gamedatas.xeno.repulse_goal);
                    coord['y'] += 40;
                    if (this.gamedatas.xeno.repulse_goal <= 12) {
                        coord['y'] += 8;
                    } else if (this.gamedatas.xeno.repulse_goal <= 20) {
                        coord['x'] += 4;
                        coord['y'] += 8;
                    }
                    dojo.place(this.format_block('jstpl_repulse_value_arrow'), 'xeno_repulse_track');
                    this.placeOnObjectPos('repulse_value_arrow', 'xeno_repulse_track', coord['x'], coord['y']);

                    $('repulse_goal').innerHTML = this.gamedatas.xeno.repulse_goal;
                    $('repulse_players').innerHTML = this.gamedatas.xeno.repulse;
                    this.setCurrentWave(this.gamedatas.xeno.current_wave, this.gamedatas.xeno.wave_remaining);
                    $('empire_defeat').innerHTML = this.gamedatas.xeno.empire_defeat;
                }

                dojo.connect(this, "updatePageTitle", this, "onUpdatePageTitle");

                // Current player hand
                var card, card_id;
                for (card_id in gamedatas.hand) {
                    card = gamedatas.hand[card_id];
                    this.playerHand.addToStockWithId(card.type, card.id);
                }

                // Tableau
                for (card_id in gamedatas.tableau) {
                    card = gamedatas.tableau[card_id];
                    this.addCardToTableau(card);
                }
                for (card_id in gamedatas.hiddentableau) {
                    card = gamedatas.hiddentableau[card_id];
                    this.addCardToTableau(card);
                }
                for (card_id in gamedatas.drafted) {
                    card = gamedatas.drafted[card_id];
                    this.deck.addToStockWithId(card.type, card.id);
                }

                // Explored
                for (card_id in gamedatas.explored) {
                    card = gamedatas.explored[card_id];
                    this.exploreSet.addToStockWithId(card.type, card.id);
                }

                // Galactic Scavenger
                for (card_id in this.gamedatas.scavenger) {
                    card = this.gamedatas.scavenger[card_id];
                    this.scavengerSet.addToStockWithId(card.type, card.id);
                }

                // Goods
                for (var i in gamedatas.good) {
                    var good = gamedatas.good[i];
                    this.addGood(good);
                }

                // Phases
                this.current_phase_choices = gamedatas.phase_choices;
                this.updatePhaseChoices(gamedatas.phase_choices);
                dojo.query('.phasechoicebtn').connect('onclick', this, 'onPhaseChoice');

                $('vp_nbr_remain').innerHTML = gamedatas.remain_chips;

                if (gamedatas.draft) {
                    dojo.style('deck', 'display', 'none');
                    dojo.query('.pdeck').style('display', 'inline-block');
                    for (var player_id in gamedatas.players) {
                        if (gamedatas.pdeck[player_id]) {
                            $(`pdeck_${player_id}`).innerHTML = gamedatas.pdeck[player_id];
                        }
                    }
                }
                else {
                    dojo.query('.pdeck').style('display', 'none');
                    $('deck_size').innerHTML = gamedatas.deck;
                }

                dojo.query('.gambling_button').connect('onclick', this, 'onGambling');

                dojo.connect($('end_military_discard'), 'onclick', this, 'onEndMilitaryDiscard');
                dojo.connect($('end_military_consume'), 'onclick', this, 'onEndMilitaryDiscard');

                // Tooltips
                this.addTooltip('vp_nbr_remain', _('Remaining victory point chips'), '');
                this.addTooltip('vp_nbr_remain_img', _('Remaining victory point chips'), '');
                this.addTooltip('deck', _('Remaining cards in deck'), '');
                this.addTooltipToClass('cardhandcount', _('Number of cards in hands'), '');
                this.addTooltipToClass('vpchipcount', _('Number of victory chips'), '');
                this.addTooltipToClass('prestigepanelcount', _("Number of prestige chips"), '');
                this.addTooltipToClass('tableaucount', _('Number of cards in tableau'), '');
                this.addTooltipToClass('xenomilforce_wrap', _("Admiral disc position for the next invasion step : Military + Military vs Xenos (Greatest Admiral wins 5 VP at the end of the game if the Empire is not defeated)"), '');
                this.addTooltipToClass('effort_wrap', _("Contribution to the War effort (Greatest Contributor wins 5 VP at the end of the game if the Empire is not defeated)"), '');
                this.addTooltipToClass('defenseaward_wrap', _("Defense Awards gained by successfully defending against Xeno invasions"), '');

                this.addTooltipHtml('explore_phase', '<h3>' + _("Explore") + '</h3><hr/><div class="cardtooltip">' + _("Draw 2 cards and keep one.") + '</div>', '');
                this.addTooltipHtml('develop_phase', '<h3>' + _("Develop") + '</h3><hr/><div class="cardtooltip">' + _("Place a development card.") + '</div>', '');
                this.addTooltipHtml('settle_phase', '<h3>' + _("Settle") + '</h3><hr/><div class="cardtooltip">' + _("Place a world card.") + '</div>', '');
                this.addTooltipHtml('consume_phase', '<h3>' + _("Consume") + '</h3><hr/><div class="cardtooltip">' + _("Use your consume powers to discard goods for Victory points and/or card draws.") + '</div>', '');
                this.addTooltipHtml('produce_phase', '<h3>' + _("Produce") + '</h3><hr/><div class="cardtooltip">' + _("Place a good on each Production world.") + '</div>', '');

                this.addTooltipOnPhaseButtons(false);

                this.addTooltipToClass('imperium_vulnerability', _("This player is vulnerable to Rebel takeovers"), '');
                this.addTooltipToClass('rebel_vulnerability', _("This player is vulnerable to Imperium takeovers"), '');
                this.addTooltipToClass('prestige_search', _("This player hasn't used their prestige/search action card"), '');
                this.addTooltipToClass('pdeck', _("Remaining cards in player's deck"), '');
                this.addTooltipToClass('orb_card_a', _("Number of A orb cards in hand"), '');
                this.addTooltipToClass('orb_card_b', _("Number of B orb cards in hand"), '');
                this.addTooltipToClass('artefact_A', _("Number of unused A artifacts"), '');
                this.addTooltipToClass('artefact_B', _("Number of unused B artifacts"), '');

                // Dynamic tooltip for breeding tube spaces
                $('orbsquares').setAttribute('tooltipText',
                    this.format_block('jstpl_breeding_tube_tooltip', {
                        'text': _("There is a breeding tube on this space")
                    })
                );
                if (this.isTouchInterface()) {
                    var breedingTubeTooltip = new dijit.Tooltip({
                        position: this.defaultTooltipPosition,
                        getContent: function(){
                            return $('orbsquares').getAttribute('tooltipText');
                        }
                    });
                    this.registerLongPressTooltip($('orbsquares'), breedingTubeTooltip, '.breeding_tube');
                } else {
                    new dijit.Tooltip({
                        connectId: "orbsquares",
                        selector: ".breeding_tube",
                        getContent: function(){
                            return $('orbsquares').getAttribute('tooltipText');
                        }
                    });
                }

                this.setupNotifications();

                this.updateSpecializedMilitary();
                this.updateVulnerabilities();

                if ($('scavengercount')) {
                    $('scavengercount').innerHTML = gamedatas.scavengercount;
                }
                dojo.query('.prestigeleadercount').forEach(dojo.hitch(this, function(node) {
                    node.innerHTML = this.gamedatas.prestigeleadercount;
                }));
            },
            initPreferences: function() {
                console.log("user preferences");
                // Card size
                var card_size;
                if (this.bga.userPreferences.get(2).toString() > "0") {
                    card_size = this.bga.userPreferences.get(2).toString();
                } else {
                    // Choose a size that lets us fit 10 cards in width
                    var area_width = dojo.style('game_play_area', 'width');
                    if (area_width >= 2380) {
                        card_size = "5";
                        dojo.query('html').addClass('card_size_huge');
                    } else if (area_width >= 2007) {
                        card_size = "4";
                        dojo.query('html').addClass('card_size_large');
                    } else if (area_width >= 1586) {
                        card_size = "3";
                        dojo.query('html').addClass('card_size_medium');
                    } else if (area_width >= 1375) {
                        card_size = "2";
                        dojo.query('html').addClass('card_size_small');
                    } else {
                        card_size = "1";
                        dojo.query('html').addClass('card_size_tiny');
                    }
                }
                this.card_size = this.optionToCardSize(card_size);

                // Card on tooltip size
                var tooltip_card_size = null;
                if (this.bga.userPreferences.get(3).toString() > "0") {
                    tooltip_card_size = this.bga.userPreferences.get(3).toString();
                } else if (card_size == "1") {
                    // For tiny size, default to medium tooltip
                    tooltip_card_size = "3";
                    dojo.query('html').addClass('card_tooltip_size_medium');
                } else if (card_size == "2") {
                    // For small size, default to large tooltip
                    tooltip_card_size = "4";
                    dojo.query('html').addClass('card_tooltip_size_large');
                } else if (card_size == "3") {
                    // For a medium size, default to huge tooltip
                    tooltip_card_size = "5";
                    dojo.query('html').addClass('card_tooltip_size_huge');
                }
                // Large and huge don't need a card tooltip
                if (tooltip_card_size > "1") {
                    this.tooltip_card_size = this.optionToCardSize(tooltip_card_size);
                }

                // tooltip delay
                switch (this.bga.userPreferences.get(4).toString()) {
                    case 0: // auto
                    case "0":
                        if (tooltip_card_size > "1") {
                            // If we have a card on the tooltip, better use the default 400ms
                            this.tooltip_delay = 400;
                        } // Otherwise, keep instant tooltip as it always was
                        break;
                    case "1":
                        this.tooltip_delay = 0;
                        break;
                    case "2":
                        this.tooltip_delay = 200;
                        break;
                    case "3":
                        this.tooltip_delay = 400;
                        break;
                    case "4":
                        this.tooltip_delay = 600;
                        break;
                    case "5":
                        this.tooltip_delay = 800;
                        break;
                    case "6":
                        this.tooltip_delay = 1000;
                        break;
                }
            },

            optionToCardSize: function(option) {
                var ret = [];
                switch (option) {
                    case "1": // tiny
                        ret['w'] = 112.43;
                        ret['h'] = 160;
                        ret['r'] = 4;
                        break;
                    case "2": // small
                        ret['w'] = 133.5;
                        ret['h'] = 190;
                        ret['r'] = 5;
                        break;
                    case "3": // medium
                        ret['w'] = 154.6;
                        ret['h'] = 220;
                        ret['r'] = 6;
                        break;
                    case "4": // large
                        ret['w'] = 196.76;
                        ret['h'] = 280;
                        ret['r'] = 8;
                        break;
                    case "5": // huge
                        ret['w'] = 234;
                        ret['h'] = 333;
                        ret['r'] = 10;
                }
                return ret;
            },
            /** Override this function to inject html into log items. This is a built-in BGA method.  */
            /* @Override */
            format_string_recursive : function(log, args) {
                try {
                    if (log && args && !args.processed) {
                        args.processed = true;
                        if (args.description !== undefined) {
                            args.description = this.colorToName(args.description);
                        }
                    }
                } catch (e) {
                    console.error(log,args,"Exception thrown", e.stack);
                }
                return this.inherited(arguments);
            },
            setCurrentWave: function(current_wave, remaining) {
                if (current_wave == -2) {
                    $('current_wave').innerHTML = _("First round : non invasion");
                } else if (current_wave == -1) {
                    $('current_wave').innerHTML = _("Second round : non invasion");
                } else {
                    $('current_wave').innerHTML = current_wave;
                }
                $('wave_remaining_cards').innerHTML = remaining;

            },

            addTooltipOnPhaseButtons: function(bBonus) {
                if (!bBonus) {
                    this.addTooltipHtml('phasechoicebtn_1', '<h3>' + _("Explore") + '</h3><hr/><div class="cardtooltip">' + _("Draw 2 cards and keep one.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw +1 card / keep +1 card") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_2', '<h3>' + _("Explore") + '</h3><hr/><div class="cardtooltip">' + _("Draw 2 cards and keep one.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw +5 cards") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_3', '<h3>' + _("Develop") + '</h3><hr/><div class="cardtooltip">' + _("Place a development card.") + '<br/><br/><p class="smalltext">' + _("Bonus : cost -1") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_4', '<h3>' + _("Settle") + '</h3><hr/><div class="cardtooltip">' + _("Play a world card.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw 1 card") + '</p></div>', '');
                    var trade_tooltip = '<h3>' + _("Consume") + '</h3><hr/><div class="cardtooltip">' + _("Use your consume powers to discard goods for Victory points and/or card draws.") + '<br/><br/><p class="smalltext">' + _("Bonus : sell 1 good to draw cards") + '</p><hr/><div>';
                    trade_tooltip += '<div class="trade_bonus"><span class="icon trade_n"></span><span>' + this.gamedatas.good_types[1] + '</span></div>';
                    trade_tooltip += '<div class="trade_bonus"><span class="icon trade_r"></span><span>' + this.gamedatas.good_types[2] + '</span></div>';
                    trade_tooltip += '<div class="trade_bonus"><span class="icon trade_g"></span><span>' + this.gamedatas.good_types[3] + '</span></div>';
                    trade_tooltip += '<div class="trade_bonus"><span class="icon trade_a"></span><span>' + this.gamedatas.good_types[4] + '</span></div>';
                    trade_tooltip += '</div></div>';
                    this.addTooltipHtml('phasechoicebtn_5', trade_tooltip, '');
                    this.addTooltipHtml('phasechoicebtn_6', '<h3>' + _("Consume") + '</h3><hr/><div class="cardtooltip">' + _("Use your consume powers to discard goods for Victory points and/or card draws.") + '<br/><br/><p class="smalltext">' + _("Bonus : double the number of Victory points chips provide by your consume powers") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_7', '<h3>' + _("Produce") + '</h3><hr/><div class="cardtooltip">' + _("Place a good on each Production world.") + '<br/><br/><p class="smalltext">' + _("Bonus : produce one good in one of your windfall world") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_9', '<h3>' + _("Produce : Repair") + '</h3><hr/><div class="cardtooltip">' + _("Place a good on each Production world.") + '<br/><br/><p class="smalltext">' + _("Bonus : repair two damaged worlds") + '</p></div>', '');
                } else {
                    this.addTooltipHtml('phasechoicebtn_1', '<h3>' + _("Explore") + '</h3><hr/><div class="cardtooltip">' + _("Draw 2 cards and keep one.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw +7 cards, combine them with your hand, keep +2 cards") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_2', '<h3>' + _("Explore") + '</h3><hr/><div class="cardtooltip">' + _("Draw 2 cards and keep one.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw +11 cards, combine them with your hand, keep +1 card") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_3', '<h3>' + _("Develop") + '</h3><hr/><div class="cardtooltip">' + _("Place a development card.") + '<br/><br/><p class="smalltext">' + _("Bonus : cost -3") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_4', '<h3>' + _("Settle") + '</h3><hr/><div class="cardtooltip">' + _("Play a world card.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw 1 card, reduce Civil world cost by 3 and gains +2 temporary military force") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_5', '<h3>' + _("Consume") + '</h3><hr/><div class="cardtooltip">' + _("Use your consume powers to discard goods for Victory points and/or card draws.") + '<br/><br/><p class="smalltext">' + _("Bonus : sell 1 good to draw cards with +3 bonus, double the number of Victory points chips provide by your consume powers, and may spend up to 2 cards for VPs") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_6', '<h3>' + _("Consume") + '</h3><hr/><div class="cardtooltip">' + _("Use your consume powers to discard goods for Victory points and/or card draws.") + '<br/><br/><p class="smalltext">' + _("Bonus : triple the number of Victory points chips provide by your consume powers") + '</p></div>', '');
                    this.addTooltipHtml('phasechoicebtn_7', '<h3>' + _("Produce") + '</h3><hr/><div class="cardtooltip">' + _("Place a good on each Production world.") + '<br/><br/><p class="smalltext">' + _("Bonus : draw 3 cards, produce one good in two of your windfall world") + '</p></div>', '');
                }

            },
            addTooltipOnPrestigeSearchButtons: function() {
                var offset;
                switch(this.gamedatas.players[this.player_id].color) {
                    case 'ff0000': // red
                        offset = 0;
                        break;
                    case 'ffa500': // yellow
                        offset = 1;
                        break;
                    case '008000': // green
                        offset = 2;
                        break;
                    case '0000ff': // blue
                        offset = 3;
                        break;
                    case 'ffffff': // white
                        offset = 4;
                        break;
                    case '000000': // black
                        offset = 5;
                }

                var card_size = this.tooltip_card_size;
                var div_class = '';
                // We default to a large tooltip if there's no setting
                if (!this.tooltip_card_size) {
                    card_size = this.optionToCardSize("4");
                    div_class = 'class="card_tooltip_size_large"';
                }
                var pos = this.cardBackgroundPos(40 + offset, card_size);
                this.addTooltipHtml('action_phasebonus', `<div ${div_class}><div class="card_tooltip" style="background-image: url(${g_gamethemeurl}img/cards_BoW.jpg); background-position: -${pos.backx}px -${pos.backy}px" /></div>`,  '');
                this.addTooltipHtml('action_search', `<img src=${g_gamethemeurl}img/search.jpg />`, '');
            },


            replaceImages: function(text) {
                text = text.replace(/XX/g, '<span class="icon chromosome" style="margin-bottom:-4px"></span>');
                text = text.replace(/TKO/g, '<span class="icon takeover"></span>');
                text = text.replace(/PFM/g, '<span class="icon pay_for_military"></span>');
                text = text.replace(/PRG/g, '<span class="icon prestige" style="margin-bottom:-3px"></span>');
                text = text.replace(/{(\w+)}/g, '<span class="icon $1"></span>');

                return text;
            },

            // Setup a new card div by adding card title and tooltip
            setupNewCard: function(card_div, card_type_id, div_id) {
                console.log("setupNewCard");
                var id = card_div.id;

                var card_type = this.gamedatas.card_types[card_type_id];
                var card_name = card_type.nametr; // Card name (translated)
                var tooltip = this.replaceImages(card_type.tooltip);
                var sixdev_scoring = card_type.sixdev_scoring;

                // Add card content
                var card_id = '';
                if (typeof div_id != 'undefined') {
                    // player_hand_item_XX
                    var card_id = div_id.substr(17);
                }
                dojo.place(`<div class="cardname"><span>${card_name}</span></div><div class="cardcross"/><div id="scavengericon_${card_id}" class="scavengericon"/>`, id);

                if (card_type_id == 181) {
                    dojo.place(`<div id="scavengericon_${card_id}" class="scavengericon"/>`, id);
                }
                if (sixdev_scoring != undefined) {
                    sixdev_scoring = this.replaceImages(sixdev_scoring);
                    sixdev_scoring = this.colorToName(sixdev_scoring);
                    tooltip += '<hr/>' + sixdev_scoring;
                    dojo.place(`<div id="six_dev_${card_id}" class="six_dev">${sixdev_scoring}</div>`, id);
                }

                dojo.style(id, "background-size", "auto " + this.card_size['h'] * 10 + "px");
                dojo.style(id, "border-radius", this.card_size['r'] + "px");
                $(id).setAttribute('tooltip', tooltip);

                if (this.tooltip_card_size) {
                    var card = this.cardBackgroundPos(card_type_id, this.tooltip_card_size);
                    card['card_name'] = card_name;
                    var tooltip_card = this.format_block('jstpl_tooltip_card', card);
                    $(id).setAttribute('tooltip_card', tooltip_card);
                }

                // Oort
                if (card_type_id == 220) {
                    var kind = this.gamedatas.card_types[card_type_id].kind
                    $(card_div).setAttribute('oort', this.gamedatas.good_types[kind]);
                }

                this.tooltips[id] = this.createManagedTooltipForNode($(id), dojo.hitch(this, function(node) {
                        // If we're trading, don't show the tooltip to not disrupt the price tooltip from the good.
                        // On mobile, if we're consuming, don't show the tooltip, it's triggered by tapping the good.
                        var price = false;
                        var goodsell = node.querySelector('.goodsell')
                        if (goodsell) {
                            price = goodsell.hasAttribute('price');
                        }
                        var selectedGood = node.querySelector('.selectedGood');
                        if (price || (this.isTouchInterface() && selectedGood))
                        {
                            return;
                        }

                        var tooltip = node.getAttribute('tooltip');

                        // Add oort current kind
                        if (node.hasAttribute('oort')) {
                            tooltip = tooltip.replace(/OORT_KIND/, _('Current kind:') + node.getAttribute('oort') +'<hr/>');
                        }

                        // Add card image
                        if (node.hasAttribute('tooltip_card')) {
                            tooltip += '<hr/>' + node.getAttribute('tooltip_card');
                        }

                        tooltip += '</div>';
                        return tooltip;

                    }), this.tooltip_delay);
                this.attachDesktopTooltipHoverClose(id, this.tooltips[id]);

                // Categories as classes
                for (var i in this.gamedatas.card_types[card_type_id].category) {
                    dojo.addClass(card_div, 'categ_' + this.gamedatas.card_types[card_type_id].category[i]);
                }

                if (this.gamedatas.card_types[card_type_id].kind != null) {
                    dojo.addClass(card_div, 'kind_' + this.gamedatas.card_types[card_type_id].kind);
                }

                for (phase = 2; phase <= 3; ++phase) {
                    for (var i in this.gamedatas.card_types[card_type_id].powers[phase]) {
                        var power = this.gamedatas.card_types[card_type_id].powers[phase][i];
                        if (power['power'].startsWith('good_for')) {
                            dojo.addClass(card_div, 'power_' + power['power']);
                        }
                    }
                }

                if ($('scavengericon_' + card_id)) {
                    dojo.connect($('scavengericon_' + card_id), 'onclick', this, 'onScavenger');
                }
                if (card_type_id == 181) {
                    // Galactic Scavenger
                    dojo.place('<div id="scavengercount">0</div>', card_div.id);
                    this.addTooltip('scavengercount', _("Number of cards under Galactic Scavengers"), '');
                }
            },

            onScavenger: function(evt) {
                var card_id = evt.currentTarget.id.substr(14);

                if (dojo.hasClass('player_hand_item_' + card_id, 'scavenger_selected')) {
                    dojo.removeClass('player_hand_item_' + card_id, 'scavenger_selected');
                } else {
                    dojo.query('.scavenger_selected').removeClass('scavenger_selected');
                    dojo.addClass('player_hand_item_' + card_id, 'scavenger_selected');

                    if (dojo.hasClass('player_hand_item_' + card_id, 'stockitem_selected')) {
                        // The card is selected => dont unselect it because we just add the scavenger
                        dojo.stopEvent(evt);
                    }
                }
            },

            updateSpecializedMilitary: function(specialized_military) {
                if (specialized_military == null) {
                    specialized_military = this.gamedatas.specialized_military;
                }

                for (var player_id in specialized_military) {
                    var specialized = specialized_military[player_id];
                    var tooltip = "<div class='militarytooltip'><div class='militarytt'>" + _('Military force') + "</div><hr/><div>";
                    types = ['base', 'xeno', 'rebel', '1', '2', '3', '4', 'temp'];
                    var xeno_force = 0;
                    for (var i in types) {
                        var type = types[i];
                        if (typeof specialized[type] != 'undefined') {
                            var force = specialized[type];
                            if (type == 'base' || type == 'xeno') {
                                xeno_force += parseInt(force);
                            }
                            if (force > 0) {
                                force = "+" + force;
                            }
                            tooltip += '<div class="imgtext milforce' + type + '"></div><span><b> ' + force + '</b></span><br>';
                        }
                    }
                    tooltip += "</div></div>";
                    this.addTooltipHtml('milforceicon_' + player_id, tooltip, '');

                    if (this.gamedatas.expansion == 7) {
                        $('xenomilforce_' + player_id).innerHTML = xeno_force;

                        if (typeof specialized['xeno_tiebreak'] != 'undefined') {
                            $('xenotiebreaker_' + player_id).innerHTML = '(' + specialized['xeno_tiebreak'] + ')';
                        }
                        else {
                            $('xenotiebreaker_' + player_id).innerHTML = '';
                        }
                    }
                }
            },

            updateVulnerabilities: function() {
                if (this.gamedatas.takeovers) {
                    for (var player_id in this.gamedatas.players) {
                        if (dojo.query("#tableau_panel_" + player_id + " .categ_imperium").length > 0) {
                            dojo.style('imperium_vulnerability_' + player_id, 'visibility', 'visible');
                        } else {
                            dojo.style('imperium_vulnerability_' + player_id, 'visibility', 'hidden');
                        }

                        if (dojo.query("#tableau_panel_" + player_id + " .categ_rebel.categ_military").length > 0) {
                            dojo.style('rebel_vulnerability_' + player_id, 'visibility', 'visible');
                        } else {
                            dojo.style('rebel_vulnerability_' + player_id, 'visibility', 'hidden');
                        }

                    }
                }
            },

            colorToName: function(title) {
                title = title.replace(/\*(.*?)\*/g, '<span class="keyword uplift">$1</span>');
                title = title.replace(/£(.*?)£/g, '<span class="keyword alien">$1</span>');
                title = title.replace(/\+(.*?)\+/g, '<span class="keyword imperium">$1</span>');
                title = title.replace(/\!(.*?)\!/g, '<span class="keyword rebel">$1</span>');
                title = title.replace(/\€(.*?)\€/g, '<span class="keyword terraforming">$1</span>');
                title = title.replace(/\@(.*?)\@/g, '<span class="keyword xeno">$1</span>');
                title = title.replace(/\^(.*?)\^/g, '<span class="keyword antixeno">$1</span>');

                return title;
            },

            setupNewGoal: function(card_div, card_type_id) {
                var id = card_div.id;
                var goal_type = this.gamedatas.goal_types[card_type_id];
                var name = goal_type.name;
                var description = goal_type.description;
                if (card_type_id == 225 && !this.gamedatas.takeovers) {
                    name = goal_type.no_takeover_name;
                    description = goal_type.no_takeover_description;
                }

                var tooltip = this.format_block('jstpl_goal_tooltip', {
                    'name': _(name),
                    'nb_points': goal_type.points,
                    'points': _("Points"),
                    'description': this.colorToName(_(description))
                });
                $(id).setAttribute('tooltip', tooltip);
                dojo.style(id, "background-size", "56px");

                if (goal_type.progress != null) {
                    $(id).setAttribute('progress', goal_type.progress);
                }

                this.tooltips[id] = this.createManagedTooltipForNode($(id), function(node) {
                        var tooltip = node.getAttribute('tooltip');
                        if (node.hasAttribute('progress')) {
                            tooltip += '<hr/>' + node.getAttribute('progress');
                        }
                        tooltip += '</div>';
                        return tooltip;
                    }, this.tooltip_delay);
                this.attachDesktopTooltipHoverClose(id, this.tooltips[id]);


                if (card_type_id == 226) {
                    dojo.place('<div class="prestigeleadercount">0</div>', card_div);
                }
            },

            // Add a new card to its tableau
            addCardToTableau: function(card, from, replace) {
                var player_id = card.location_arg;

                if ($('card_' + card.id) && !replace) {
                    // Already exists !
                    return;
                }

                if (replace) {
                    this.createCardAtPlace($('card_wrapper_' + card.id), card, 'replace');
                } else {
                    this.createCardAtPlace('tableau_' + player_id, card);
                    $('tableau_nbr_' + player_id).innerHTML = toint($('tableau_nbr_' + player_id).innerHTML) + 1;
                }

                if (player_id == this.player_id) {
                    dojo.connect($('card_' + card.id), 'onclick', this, 'onClickOnCardOnTableau');
                    dojo.connect($('damage_' + card.id), 'onclick', this, 'onRepairWorld');
                    this.card_to_type[card.id] = card.type;
                } else {
                    dojo.connect($('card_' + card.id), 'onclick', this, 'onClickOnOpponentCardOnTableau');
                }

                if (from != null) {
                    this.placeOnObject($('card_' + card.id), from);
                    this.slideToObject(('card_' + card.id), ('card_wrapper_' + card.id)).play();
                }

                if (card.damaged) {
                    dojo.addClass('card_' + card.id, 'damaged');
                }

                this.updateVulnerabilities();
            },

            // Move a card to another tableau
            moveCardToTableau: function(card, from) {
                var player_id = card.location_arg;

                from.setAttribute("id", "to_delete");
                from.children[0].setAttribute("id", "to_delete");

                this.createCardAtPlace('tableau_' + player_id, card);
                $('tableau_nbr_' + player_id).innerHTML = toint($('tableau_nbr_' + player_id).innerHTML) + 1;

                if (player_id == this.player_id) {
                    dojo.connect($('card_' + card.id), 'onclick', this, 'onClickOnCardOnTableau');
                    dojo.connect($('damage_' + card.id), 'onclick', this, 'onRepairWorld');
                    this.card_to_type[card.id] = card.type;
                } else {
                    dojo.connect($('card_' + card.id), 'onclick', this, 'onClickOnOpponentCardOnTableau');
                }

                this.placeOnObject($('card_' + card.id), from);
                this.slideToObject(('card_' + card.id), ('card_wrapper_' + card.id)).play();
                dojo.destroy(from);

                this.updateVulnerabilities();
            },

            cardBackground: function(card_type_id) {
                var ret = {'background':'cards.jpg',
                           'card_type_id': card_type_id};
                // Special case for oort to optimize the BoW jpg to 4 columns without changing the card id
                if (card_type_id == 220) {
                    ret['card_type_id'] = 180;
                }
                // TGS Gambling World uses the base game image now
                if (card_type_id == 110) {
                    ret['card_type_id'] = 56;
                }
                if (ret['card_type_id'] >= 100 && ret['card_type_id'] <120) {
                    ret['background'] = 'cards_TGS.jpg';
                    ret['card_type_id'] -= 100;
                } else if (ret['card_type_id'] >= 130 && ret['card_type_id'] <170) {
                    ret['background'] = 'cards_RvI.jpg';
                    ret['card_type_id'] -= 130;
                } else if (ret['card_type_id'] >= 180 && ret['card_type_id'] <= 220) {
                    ret['background'] = 'cards_BoW.jpg';
                    ret['card_type_id'] -= 180;
                } else if (ret['card_type_id'] >= 230 && ret['card_type_id'] < 270) {
                    ret['background'] = 'cards_AA.jpg';
                    ret['card_type_id'] -= 230;
                } else if (ret['card_type_id'] >= 270 && ret['card_type_id'] < 314) {
                    ret['background'] = 'cards_XI.jpg';
                    ret['card_type_id'] -= 270;
                } else if (ret['card_type_id'] >= 314 && ret['card_type_id'] < 320) {
                    ret['background'] = 'cards_NW.jpg';
                    ret['card_type_id'] -= 314;
                }
                ret['background'] = `${g_gamethemeurl}img/${ret['background']}`;
                return ret;
            },
            cardBackgroundPos: function(card_type_id, card_size) {
                var ret = this.cardBackground(card_type_id);
                ret['backx'] = card_size['w'] * Math.floor(ret['card_type_id'] / 10);
                ret['backy'] = card_size['h'] * (ret['card_type_id'] % 10);
                return ret;
            },
            // Create a new card into specific div
            createCardAtPlace: function(target_div_id, card, pos) {
                console.log('createCardAtPlace');
                console.log(card);
                if (typeof pos == 'undefined') {
                    pos = "last";
                }
                var card_type_id = toint(card.type);
                if (card.damaged) {
                    card_type_id = card.damaged;
                }
                Object.assign(card, this.cardBackgroundPos(card_type_id, this.card_size));
                console.log(card);
                dojo.place(this.format_block('jstpl_card', card), target_div_id, pos);

                this.setupNewCard($('card_' + card.id), card_type_id);
            },

            hasGamePrestige: function() {
                return this.gamedatas.expansion == 4;
            },

            numberPlayers: function() {
                return Object.keys(this.gamedatas.players).length;
            },
            phasesToChoose: function() {
                if (this.numberPlayers() == 2) {
                    return 2;
                }
                return 1;
            },

            updatePhaseChoices: function(choices) {
                console.log('updatePhaseChoices');
                console.log(choices);

                dojo.query('.unselected_phase').removeClass('unselected_phase');
                var phasename = {
                    1: 'explore',
                    2: 'develop',
                    3: 'settle',
                    4: 'consume',
                    5: 'produce'
                };
                this.phases_chosen = 0;
                for (var phase_id in choices) {
                    if (phase_id != 7) {
                        dojo.empty('phase_selected_' + phase_id);
                    }

                    var output = '<span class="phase_offset"></span>';
                    for (var player_id in choices[phase_id]) {
                        var bonus_id = toint(choices[phase_id][player_id]);
                        var num_choices = 1;
                        if (bonus_id >= 10 && player_id == this.player_id) {
                            dojo.style('prestige_search_' + player_id, 'visibility', 'hidden');
                        }
                        var symbol = 'X';
                        if (phase_id == 1) {

                            if (bonus_id >= 100) {
                                bonus_id -= 101;
                                symbol = _("Orb") + ' ';
                            } else {
                                symbol = '';
                            }

                            if (bonus_id === 0) {
                                symbol += '+1+1';
                            } else if (bonus_id === 1) {
                                symbol += '+5+0';
                            } else if (bonus_id === 2) {
                                symbol += '+6+1';
                                num_choices += 1;
                            } else if (bonus_id === 10) {
                                symbol += '+7+2';
                            } else if (bonus_id === 11) {
                                symbol += '+11+1';
                            } else if (bonus_id === 12) {
                                symbol += '+12+2';
                                num_choices += 1;
                            }
                        } else if (phase_id == 2 || phase_id == 3) {
                            if (bonus_id === 2) {
                                symbol = 'XX';
                                num_choices += 1;
                            }

                            if (phase_id == 2 && bonus_id === 10) {
                                symbol = '-3';
                            }
                            if (phase_id == 2 && bonus_id === 12) {
                                symbol = '-3 X';
                                num_choices += 1;
                            }
                            if (phase_id == 2 && bonus_id === 22) {
                                symbol = 'X -3';
                                num_choices += 1;
                            }

                            if (phase_id == 3 && bonus_id === 10) {
                                symbol = '-3+2';
                            }
                            if (phase_id == 3 && bonus_id === 12) {
                                symbol = '-3+2 X';
                                num_choices += 1;
                            }
                            if (phase_id == 3 && bonus_id === 22) {
                                symbol = 'X -3+2';
                                num_choices += 1;
                            }
                        } else if (phase_id == 4) {
                            if (bonus_id === 0) {
                                symbol = '$';
                            } else if (bonus_id === 1) {
                                symbol = 'x2';
                            } else if (bonus_id === 2) {
                                symbol = '$ x2';
                                num_choices += 1;
                            } else if (bonus_id === 10) {
                                symbol = '$+3 / x2';
                            } else if (bonus_id === 11) {
                                symbol = 'x3';
                            } else if (bonus_id === 12) {
                                symbol = '$+3 / x3';
                                num_choices += 1;
                            }
                        } else if (phase_id == 5) {
                            if (Math.floor(bonus_id / 10) == 1) {
                                symbol = $('prodboosted').innerHTML;
                            }

                            if (bonus_id == 3) {
                                symbol = '<i class="fa fa-wrench fa-lg" aria-hidden="true"></i>';
                            }
                            if (bonus_id > 3) {
                                symbol = 'X<i class="fa fa-wrench fa-lg" aria-hidden="true"></i>';
                            }
                        }

                        // Forced settle in turn 1 in XI
                        if (player_id == -1) {
                            output = '<span/>';
                        } else if (phase_id != 7) {
                            var color = this.gamedatas.players[player_id].color;
                            output += '<span style="color:#' + color + '">' + symbol + '</span>';
                        }
                        if (player_id == this.player_id) {
                            this.phases_chosen += num_choices;
                        }
                    }
                    if (output != '<span class="phase_offset"></span>') {
                        dojo.place(output, 'phase_selected_' + phase_id);
                    } else if (phase_id != 7) {
                        // This phase is unselected
                        console.log('set phase ' + phase_id + ' as unselected');
                        var phase_name = phasename[phase_id];
                        dojo.addClass($(phase_name + '_phase'), 'unselected_phase');
                    }
                }

                if ((this.gamedatas.gamestate.name == "phaseChoice"
                     || this.gamedatas.gamestate.name == "phaseChoiceCrystal")
                        // check that the buttons are already created (we are sometimes called earlier)
                        && dojo.query('#action_phaseCancel').length > 0) {
                    // If at least one phase has been selected, show the cancel button
                    // otherwise hide it, and check if prestige and search buttons need to be restored
                    if (this.phases_chosen > 0) {
                        dojo.style('action_phaseCancel', 'display', 'inline');
                    } else {
                        dojo.style('action_phaseCancel', 'display', 'none');
                    }
                    if (this.isCurrentPlayerActive()) {
                        const selectionDone = (this.phases_chosen > 1
                                               || (this.numberPlayers() > 2 && this.phases_chosen > 0)); // FIXME psi-crystal
                        if (this.phaseSelectNeedsConfirm()) {
                            dojo.style('phase_select_confirm', 'display', 'inline');
                        }
                        if (selectionDone) {
                            dojo.style('phasechoice_panel', 'display', 'none');
                            dojo.query('#phase_select_confirm').removeClass('disabled');
                            if (this.hasGamePrestige()) {
                                dojo.style('action_phasebonus', 'display', 'none');
                                dojo.style('action_search', 'display', 'none');
                                dojo.style('action_cancelphasebonus', 'display', 'none');
                            }
                        } else {
                            dojo.style('phasechoice_panel', 'display', 'block');
                            dojo.query('#phase_select_confirm').addClass('disabled');
                            if(this.hasGamePrestige()) {
                                if (this.gamedatas.gamestate.args.searchavail[this.player_id] == 1) {
                                    dojo.style('action_phasebonus', 'display', 'inline');
                                    dojo.style('action_search', 'display', 'inline');
                                } else {
                                    dojo.style('action_phasebonus', 'display', 'none');
                                    dojo.style('action_search', 'display', 'none');
                                }
                            }
                        }
                    } else {
                        dojo.style('phasechoice_panel', 'display', 'none');
                        // remaining buttons do not exist
                        /*
                        dojo.style('phase_select_confirm', 'display', 'none');
                        dojo.style('action_phasebonus', 'display', 'none');
                        dojo.style('action_search', 'display', 'none');
                        dojo.style('action_cancelphasebonus', 'display', 'none');
                        */
                    }
                }
            },

            // Enlight current phase
            setActivePhase: function(phase_name) {
                dojo.query('.current_phase').removeClass('current_phase');
                dojo.addClass($(phase_name + '_phase'), 'current_phase');
            },

            // Add a good (good = {world_id, good_id, good_type})
            addGood: function(good) {
                console.log('addGood');
                console.log(good);

                var world_div = $('card_' + good.world_id);
                if (!world_div) {
                    console.error('producing in unknow world');
                    return;
                }

                if ($('good_wrap_' + good.good_id)) {
                    // Remove the previous one before
                    dojo.destroy('good_wrap_' + good.good_id);
                }

                // If there is still a destruction pending, do it now
                if ($('good_wrap_to_destroy')) {
                    dojo.destroy('good_wrap_to_destroy');
                }

                good.indicator = '';
                if (dojo.query('#good_place_' + good.world_id + ' .good_wrap').length > 0) {
                    // Add a "xN" indicator
                    good.indicator = '&nbsp;x' + (1 + dojo.query('#good_place_' + good.world_id + ' .good_wrap').length);
                }


                dojo.place(this.format_block('jstpl_good', good), 'good_place_' + good.world_id);

                dojo.connect($('good_' + good.good_id), 'onclick', this, 'onClickOnGood');

                this.tooltips['goodsell_' + good.good_id] = this.createManagedTooltipForNode($('goodsell_' + good.good_id), function(node) {
                        var price = node.getAttribute('price');
                        if (price != null)
                        {
                            return _('Trade price: ') + price;
                        }
                    }, this.tooltip_delay);
            },
            updateWindfallPowers: function(possibilities) {
                if (!this.isCurrentPlayerActive()) {
                    return;
                }

                if (typeof(possibilities['single_power']) == 'undefined') {
                    dojo.empty('windfallpowers');
                }

                for (var i in possibilities) {
                    if (i == 'single_power') {
                        continue;
                    }

                    var possibility = possibilities[i];
                    if (i == 'title') {
                        this.updateProduceTitle(possibility);
                        continue;
                    }

                    var css_class = "windfallpower" + possibility.type;
                    if (possibility.world_type != null) {
                        css_class += possibility.world_type;
                    }

                    dojo.place("<div class='windfallpower " + css_class + "' id='windfallpower_" + possibility.reason + "'></div>", 'windfallpowers');
                    var tooltip;

                    switch (possibility.type) {
                        case 'all':
                            tooltip = _("You may produce on a windfall world");
                            break;

                        case 1:
                        case 2:
                        case 3:
                        case 4:
                            var world_type = this.gamedatas.good_types[possibility.type];
                            tooltip = dojo.string.substitute(_("You may produce on a ${type} windfall world"), {
                                type: world_type
                            });
                            break;

                        case 'produceifdiscard':
                            var card_type_id = this.card_to_type[possibility.reason];
                            var world_name = this.gamedatas.card_types[card_type_id].name;
                            tooltip = dojo.string.substitute(_("You may produce on ${world} if you discard a card"), {
                                world: world_name
                            });
                            break;

                        case 'windfallproduceifdiscard':
                            if (possibility.world_type == null) {
                                tooltip = _("You may produce on a windfall world if you discard a card");
                            } else {
                                var world_type = this.gamedatas.good_types[parseInt(possibility.world_type)];
                                tooltip = dojo.string.substitute(_("You may produce on a ${type} windfall world if you discard a card"), {
                                    type: world_type
                                });
                            }
                            break;

                        case 'repair':
                            tooltip = _("You may repair a damaged world");
                            break;

                        case 'drawforeachworld':
                            var world_type = this.gamedatas.good_types[possibility.world_type];
                            tooltip = dojo.string.substitute(_("Draw a card for each ${type} world in your tableau"), {
                                type: world_type
                            });
                            dojo.connect($('windfallpower_' + possibility.reason), 'onclick', this, 'drawForEachWorld');
                            break;

                        case 'drawformilitary':
                            tooltip = _("Draw a card for each two Military worlds in your tableau");
                            dojo.connect($('windfallpower_' + possibility.reason), 'onclick', this, 'drawForEachWorld');
                            break;

                        case 'drawforxenomilitary':
                            tooltip = _("Draw a card for each Xeno Military world in your tableau");
                            dojo.connect($('windfallpower_' + possibility.reason), 'onclick', this, 'drawForEachWorld');
                            break;

                        case 'drawforeachgoodtype':
                            tooltip = _("Draw a card for each different type of good produced");
                            dojo.connect($('windfallpower_' + possibility.reason), 'onclick', this, 'drawForEachGood');
                            break;

                        case 'drawforeach':
                            var world_type = this.gamedatas.good_types[possibility.world_type];
                            tooltip = dojo.string.substitute(_("Draw a card for each ${good} you produce"), {
                                good: world_type
                            });
                            dojo.connect($('windfallpower_' + possibility.reason), 'onclick', this, 'drawForEachGood');
                            break;

                        case 'drawforeachtwo':
                            tooltip = _("Draw a card for each two resource you produce");
                            dojo.connect($('windfallpower_' + possibility.reason), 'onclick', this, 'drawForEachGood');
                            break;
                    }
                    this.addTooltip('windfallpower_' + possibility.reason, tooltip, '');
                }
            },

            getXenoTrackCoord: function(force, tiebreak, animate) {
                var coord = new Object();
                if (force <= 5) {
                    coord['y'] = -160;
                    coord['x'] = -112 + Math.max(force, -3) * 70.5;
                } else if (force <= 12) {
                    coord['y'] = -66;
                    coord['x'] = -218 + (force - 6) * 70.5;
                } else if (force <= 20) {
                    coord['y'] = 27;
                    coord['x'] = -248 + (force - 13) * 70.5;
                } else if (force <= 30) {
                    coord['y'] = 101;
                    coord['x'] = -255 + (force - 21) * 56.7;
                } else {
                    coord['y'] = 167;
                    coord['x'] = -255 + (Math.min(force, 40) - 31) * 56.7;
                }

                if (tiebreak != null) {
                    coord['y'] -= (tiebreak - 1) * 8;
                }

                if (animate) {
                    coord['x'] += 275;
                    coord['y'] += 187;
                }

                return coord;
            },

            placeAdmiralDisks: function(player_milforce) {
                var xeno_milforce;
                var xeno_milforce_tiebreak;
                var xeno_repulse = 0;

                for (var player_id in this.gamedatas.players) {
                    var player = this.gamedatas.players[player_id];

                    if (player_milforce == null) {
                        xeno_milforce = player.xeno_milforce;
                        xeno_milforce_tiebreak = player.xeno_milforce_tiebreak;
                        xeno_repulse = this.gamedatas.xeno.repulse;
                    } else {
                        xeno_milforce = parseInt(player_milforce[player_id].xeno_milforce);
                        xeno_milforce_tiebreak = parseInt(player_milforce[player_id].xeno_milforce_tiebreak);
                        xeno_repulse += xeno_milforce;
                    }
                    var coord = this.getXenoTrackCoord(xeno_milforce, xeno_milforce_tiebreak);
                    dojo.place(this.format_block('jstpl_admiral', {
                        id: player.id,
                        color: player.color
                    }), 'xeno_repulse_track');
                    dojo.style('admiral_' + player.id, 'zIndex', xeno_milforce_tiebreak);
                    this.placeOnObjectPos('admiral_' + player.id, 'xeno_repulse_track', coord['x'], coord['y']);
                }

                var coord = this.getXenoTrackCoord(xeno_repulse);
                coord['y'] += 70;
                if (xeno_repulse <= 12) {
                    coord['x'] += 1;
                } else if (xeno_repulse <= 20) {
                    coord['x'] += 4;
                }
                dojo.place(this.format_block('jstpl_military_vs_xeno_arrow'), 'xeno_repulse_track');
                this.placeOnObjectPos('military_vs_xeno_arrow', 'xeno_repulse_track', coord['x'], coord['y']);
            },

            ///////////////////////////////////////////////////
            //// Game & client states

            onEnteringState: function(stateName, args) {
                console.log('Entering state: ' + stateName);
                console.log(args);

                switch (stateName) {
                    case 'initialDiscardHomeWorld':
                        dojo.style('my_deck_wrap', 'display', 'none');
                        break;
                    case 'initialDiscardAncientRace':
                        dojo.addClass('hand_panel', 'paymentMode');
                        break;
                    case 'initialDiscardScavenger':
                        dojo.addClass('hand_panel', 'paymentModeScavenger');
                        break;
                    case 'phaseChoice':
                        dojo.style($('phasechoice_panel'), 'display', 'block');
                        dojo.query('.current_phase').removeClass('current_phase');
                        this.current_phase_choices = args.args.phasechoices;
                        this.updatePhaseChoices(args.args.phasechoices);

                        if (this.gamedatas.expansion == 5) {
                            // Orb play
                            dojo.style('phasechoicebtn_8', 'display', 'inline');
                            dojo.addClass('phasechoice_panel', 'orbavail');
                        }

                        break;
                    case 'phaseChoiceCrystal':
                        dojo.style($('phasechoice_panel'), 'display', 'block');
                        break;
                    case 'breedingTube':
                        if (this.isCurrentPlayerActive()) {
                            dojo.addClass(`orbcard_${args.args.orb_card_under.id}`, 'orbCardUnder');
                            dojo.addClass(`player_hand_orb_item_${args.args.latest}`, 'selectedOrbCard');
                        }
                        break;
                    case 'explore':
                        if (typeof(args.args[this.player_id]) != 'undefined' && typeof(args.args[this.player_id].mix) != 'undefined'
                            || this.isSpectator) {
                            // Explore mix => we do not need explore panel, and we must adapt the status bar title
                        } else {
                            dojo.style($('explore_panel'), 'display', 'block');
                        }
                        this.exploreSet.updateDisplay();
                        this.setActivePhase('explore');
                        this.gamedatas.orbteamhasmoved = 0;
                        break;
                    case 'develop':
                    case 'settle':
                        this.setActivePhase(stateName);
                        if (this.exploreSet.items.length > 0) {
                            this.explorePanelTitle = dojo.query('#explore_panel > h3')[0].innerHTML;
                            dojo.query('#explore_panel > h3')[0].innerHTML = _("Scavenging");
                            dojo.style('explore_panel', 'display', 'block');
                            dojo.style('action_nothing_to_play', 'display', 'none');
                            this.exploreSet.updateDisplay();
                            this.lastPaymentTitle = $('pagemaintitletext').innerHTML;
                            $('pagemaintitletext').innerHTML = _("You must choose one card to save under Galactic Scavengers");
                            this.scavenging = true;
                        }
                        this.paymentMode = false;
                        dojo.removeClass('hand_panel', 'paymentMode');
                        this.nextCardToPlay = null;
                        this.paymentCost = 0;
                        this.immediateAlternatives = null;
                        this.isMilitarySettle = false;
                        break;
                    case 'consumesell':
                        this.setActivePhase('consume');
                        dojo.query('.goodsell').style('display', 'block');
                        for (var good_id in args.args) {
                            $(`goodsell_${good_id}`).setAttribute('price', args.args[good_id]);
                        }
                        break;
                    case 'consume':
                        this.setActivePhase('consume');
                        dojo.style('consume_help', 'display', 'block');
                        if (args.args.gambling) {
                            if (typeof args.args.gambling[this.player_id] != 'undefined') {
                                dojo.style('gambling_panel', 'display', 'block');
                            }
                        }
                        if (this.exploreSet.items.length > 0) {
                            dojo.style('explore_panel', 'display', 'block');
                            dojo.style('action_stopConsumption', 'display', 'none');
                            this.explorePanelTitle = dojo.query('#explore_panel > h3')[0].innerHTML;
                            dojo.query('#explore_panel > h3')[0].innerHTML = _("Gambling Result");
                            this.exploreSet.updateDisplay();
                            this.lastPaymentTitle = $('pagemaintitletext').innerHTML;
                            $('pagemaintitletext').innerHTML = _("You must choose one card to keep");
                        }
                        break;
                    case 'productionwindfall':
                        this.setActivePhase('produce');
                        if ($('windfallpowers')) {
                            dojo.empty('windfallpowers');
                        } else {
                            dojo.place("<div id='windfallpowers'></div>", 'pagemaintitle_wrap');
                        }
                        this.updateWindfallPowers(this.gamedatas.gamestate.args[this.player_id]);
                        break;
                    case 'draft':
                        dojo.style('my_deck_wrap', 'display', 'block');
                        break;
                    case 'endRound':
                        dojo.query('.prestigeleadercount').forEach(dojo.hitch(this, function(node) {
                            node.innerHTML = 0;
                        }));
                        for (var i = 1; i <= 5; i++) {
                            dojo.empty('phase_selected_' + i);
                        }

                        break;
                    case 'orbActionMove':
                        dojo.query('.activeTeam').removeClass('activeTeam');
                        for (var i in args.args.teams) {
                            dojo.addClass('team_' + args.args.teams[i], 'activeTeam');
                        }
                        break;
                    case 'orbActionMoveDest':
                        dojo.query('.teamSelected').removeClass('teamSelected');
                        dojo.addClass('team_' + args.args.team, 'teamSelected');
                        dojo.query('.possibledest').removeClass('possibledest');

                        for (var i in args.args.moves) {
                            dojo.addClass('orb_' + args.args.moves[i], 'possibledest');
                        }

                        break;
                    case 'invasionGame':
                        // save the scroll position
                        this.scroll_pos = window.scrollY;
                        break;
                    case 'invasionGameResolution':
                        for (var player_id in args.args.invasion) {
                            this.updateInvasion(player_id, args.args.invasion[player_id]);
                            this.gamedatas.players[player_id].bunker_used = 0;
                        }


                        break;
                }
            },
            onLeavingState: function(stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                    case 'initialDiscardScavenger':
                        dojo.removeClass('hand_panel', 'paymentModeScavenger');
                        break;
                    case 'phaseChoice':
                    case 'phaseChoiceCrystal':
                        dojo.style($('phasechoice_panel'), 'display', 'none');
                        dojo.destroy($('action_phaseCancel'));
                        break;
                    case 'breedingTube':
                        dojo.query('.orbCardUnder').removeClass('orbCardUnder');
                        break;
                    case 'explore':
                        dojo.style($('explore_panel'), 'display', 'none');
                        this.exploreSet.removeAll();
                        dojo.removeClass('hand_panel', 'paymentMode');
                        break;
                    case 'settle':
                        dojo.query('.mercenarySelected').removeClass('mercenarySelected');
                        dojo.query('.consumeformilitarySelected').removeClass('consumeformilitarySelected');
                    case 'develop':
                        // Remove any previous card which was not actually played.
                        // This is necessary as the onDontPay is not triggered by a notification.
                        if (this.nextCardToPlay) {
                            dojo.query('.nextCardToPlay').removeClass('nextCardToPlay');
                            this.playerHand.addToStockWithId(this.nextCardToPlay.type, this.nextCardToPlay.id, $('card_' + this.nextCardToPlay.id));
                            dojo.destroy($('card_wrapper_' + this.nextCardToPlay.id));
                            $('tableau_nbr_' + this.player_id).innerHTML = toint($('tableau_nbr_' + this.player_id).innerHTML) - 1;
                        }

                        this.paymentMode = false;
                        this.nextCardToPlay = null;
                        this.paymentCost = 0;
                        this.immediateAlternatives = null;
                        this.isMilitarySettle = false;
                        break;
                    case 'consumesell':
                        dojo.query('.goodsell').style('display', 'none');
                        dojo.query('[price]').removeAttr('price');
                        break;
                    case 'consume':
                        dojo.style('consume_help', 'display', 'none');
                        dojo.style('gambling_panel', 'display', 'none');
                        break;
                    case 'productionwindfall':
                        if ($('windfallpowers')) {
                            dojo.empty('windfallpowers');
                        }
                        this.gamedatas.produced_goods = [0, 0, 0, 0, 0];
                        break;
                    case 'initialDiscard':
                    case 'endrounddiscard':
                        dojo.removeClass('hand_panel', 'paymentMode');
                        break;
                    case 'takeover_attackerboost':
                    case 'takeover_defenderboost':
                        dojo.query('.mercenarySelected').removeClass('mercenarySelected');
                        dojo.query('.consumeformilitarySelected').removeClass('consumeformilitarySelected');
                        break;
                    case 'settletakeoverresolution':
                        for (var i in this.gamedatas.players) {
                            $('tmpmilforce_' + i).innerHTML = '';
                        }
                        break;
                    case 'orbActionMoveDest':
                    case 'orbActionBackToSas':
                        dojo.query('.teamSelected').removeClass('teamSelected');
                        dojo.query('.possibledest').removeClass('possibledest');

                        break;
                    case 'invasionGameResolution':
                        for (var player_id in this.gamedatas.players) {
                            this.updateInvasion(player_id);
                        }
                        break;

                }
            },
            onUpdatePageTitle: function() {
                console.log('onUpdatePageTitle');
                switch (this.gamedatas.gamestate.name) {

                    case 'initialDiscard':
                        // If starting world = Ancient (107), we must discard 3 cards instead of one
                        if (this.isCurrentPlayerActive() &&
                            dojo.query('#tableau_' + this.player_id + ' .card_type_107').length == 1) {
                            $('pagemaintitletext').innerHTML = $('pagemaintitletext').innerHTML.replace(' 2 ', ' 3 ');
                            document.title = document.title.replace(' 2 ', ' 3 ');
                        }

                        break;

                    case 'explore':
                        if ($('titlearg1')) {
                            $('titlearg1').innerHTML = this.gamedatas.gamestate.args[this.player_id].keep;
                        }
                        break;
                    case 'endrounddiscard':
                        if ($('titlearg1')) {
                            $('titlearg1').innerHTML = this.gamedatas.gamestate.args[this.player_id];
                        }
                        break;
                    case 'developdiscard':
                        if ($('titlearg1')) {
                            $('titlearg1').innerHTML = this.gamedatas.gamestate.args[this.player_id];
                        }
                        break;
                    case 'settlediscard':
                        if ($('titlearg1')) {
                            $('titlearg1').innerHTML = this.gamedatas.gamestate.args[this.player_id];
                        }
                        break;
                    case 'settle':
                    case 'develop':
                        this.updatePageTitleForCost();
                        if (this.immediateAlternatives !== null && this.immediateAlternatives.length > 0) {
                            var phase = 'phase';
                            if (this.gamedatas.gamestate.name === 'develop') {
                                phase = _("Develop");
                            } else {
                                phase = _("Settle");
                            }
                            const title = dojo.string.substitute(_('${phase}: Choose an action'), {
                                phase: phase});
                            $('pagemaintitletext').innerHTML = title
                            document.title =  title
                        }
                        break;
                    case 'productionwindfall':
                        if (!this.isCurrentPlayerActive() && $('windfallpowers')) {
                            dojo.empty('windfallpowers');
                        }
                        break;
                    case 'invasionGameResolution':
                        if ($('titlearg1')) {
                            $('titlearg1').innerHTML = this.gamedatas.gamestate.args.invasion[this.player_id];
                        }
                        if ($('titlearg2')) {
                            $('titlearg2').innerHTML = this.gamedatas.gamestate.args.force[this.player_id];
                        }
                        break;

                }
            },
            onUpdateActionButtons: function(stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                switch (stateName) {
                    case 'phaseChoice':
                    case 'phaseChoiceCrystal':
                        if (this.isCurrentPlayerActive()) {
                            dojo.style($('phasechoice_panel'), 'display', 'block');
                            if (this.phaseSelectNeedsConfirm()) {
                                this.addActionButton('phase_select_confirm', _("Done"), 'onPhaseSelectConfirm');
                                if (this.phases_chosen != this.phasesToChoose()) {
                                    dojo.query('#phase_select_confirm').addClass('disabled');
                                }
                            }
                            if (this.hasGamePrestige()) {
                                this.addActionButton('action_phasebonus', _("Use bonus card for phase bonus"), 'onPhaseBonus');
                                if (!args.hasprestige[this.player_id]) {
                                    dojo.query('#action_phasebonus').addClass('disabled');
                                }
                                this.addActionButton('action_search', _("Use bonus card for search action"), 'onSearchAction');
                                this.addActionButton('action_cancelphasebonus', _("Cancel bonus card use"), 'onCancelPhaseBonus');
                                dojo.style('action_cancelphasebonus', 'display', 'none');
                                this.addTooltipOnPrestigeSearchButtons();
                                if (this.gamedatas.gamestate.args.searchavail[this.player_id] != 1) {
                                    dojo.style('action_phasebonus', 'display', 'none');
                                    dojo.style('action_search', 'display', 'none');
                                }
                            }
                        }
                        if (!this.isSpectator) {
                            this.addActionButton('action_phaseCancel', _("Cancel"), 'onPhaseCancel', null, false, 'red');

                            if (this.phases_chosen === 0) {
                                dojo.style('action_phaseCancel', 'display', 'none');
                            }
                        }
                        break;

                    case 'orbActionMove':
                        if (this.isCurrentPlayerActive()) {
                             if (this.gamedatas.orbteamhasmoved > 0) {
                                this.addActionButton('action_orbendmoveaction', _("End Move Action"), 'onOrbEndMoveAction');
                            } else {
                                this.addActionButton('action_orbpass', _("Pass orb step"), 'onOrbPass');
                                this.addActionButton('action_orbskip', _("Skip action"), 'onOrbSkip');
                            }
                         }
                        break;
                    case 'orbActionMoveDest':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_orbpass', _("Stop here"), 'onOrbStop');
                        }
                        break;
                    case 'orbActionBackToSas':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_orbdone', _("Done"), 'onOrbSkip');
                        }
                        break;

                    case 'orbActionPlay':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_orbskip', _("Skip action"), 'onOrbSkip');
                        }
                    // fallthrough
                    case 'initialOrb':
                    case 'breedingTube':
                    case 'additionalSas':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_orbcancel', _("Cancel"), 'onOrbCancel');
                            this.addActionButton('action_orbconfirm', _("Confirm"), 'onOrbConfirm');
                            dojo.style('action_orbcancel', 'display', 'none');
                            dojo.style('action_orbconfirm', 'display', 'none');
                        }
                        break;

                    case 'orbActionDraw':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_orbdraw', _("Draw Orb card"), 'onOrbDraw');
                            this.addActionButton('action_orbskip', _("Skip action"), 'onOrbSkip');
                        }
                        break;
                    case 'explore':
                        if (this.isCurrentPlayerActive()) {
                            if (this.exploreNeedsConfirm()) {
                                this.addActionButton('action_explore_confirm', _("Done"), 'onExploreDone');
                                dojo.query('#action_explore_confirm').addClass('disabled');
                            }
                        }
                        if (this.isCurrentPlayerActive() && this.playerHasExploreMix()) {
                            var mustDiscard = Math.max(0, args[this.player_id].draw - args[this.player_id].keep);
                            $('pagemaintitletext').innerHTML = dojo.string.substitute(_("Explorer : you must discard ${nbr} cards"), {
                                nbr: mustDiscard
                            });
                            dojo.addClass('hand_panel', 'paymentMode');
                        }

                        break;

                    case 'develop':
                    case 'settle':
                        if (this.isCurrentPlayerActive()) {
                            if (this.immediateAlternatives !== null && this.immediateAlternatives.length > 0) {
                                var i = 0;
                                for (const alternative of this.immediateAlternatives) {
                                    var buttonID = `btn_immediate_alternative_${i}`;
                                    var buttonIcon = '';
                                    var buttonLabel = '';
                                    if (alternative.kind === 'military' || alternative.kind === 'pay') {
                                        buttonID = `btn_immediate_alternative_${alternative.kind}`;
                                        if (alternative.kind === 'military') {
                                            buttonIcon = '<span class="icon military_world"></span> ';
                                            buttonLabel = _('Conquer');
                                        } else {
                                            const target = this.gamedatas.card_types[this.nextCardToPlay.type];
                                            if (target.type === 'world') {
                                                if (target.category.includes('military')) {
                                                    buttonIcon = '<span class="icon pay_for_military"></span> ';
                                                } else {
                                                    buttonIcon = '<span class="icon civil_world"></span> ';
                                                }
                                            } else {
                                                buttonIcon = '<span class="icon development"></span>';
                                            }
                                            buttonLabel = dojo.string.substitute(_("Pay ${cost} cards"), {
                                                cost: this.paymentCost
                                            });
                                        }
                                    } else {
                                        const card_type = this.card_to_type[alternative.card_id];
                                        const card_name = this.gamedatas.card_types[card_type].name;
                                        buttonLabel = dojo.string.substitute(_("Use ${name}"), {
                                            name: card_name
                                        });
                                    }
                                    this.addActionButton(
                                        buttonID, buttonIcon + buttonLabel,
                                        () => {this.onHandleImmediateAlternative(alternative);});
                                    i = i + 1;
                                }
                                if (this.paymentCost > 0) {
                                    dojo.query('#btn_immediate_alternative_pay').addClass('disabled');
                                }
                            }
                            if (!this.paymentMode) {
                                this.addActionButton('action_nothing_to_play', _("I won't"), 'onNothingToPlay');
                                if (this.exploreSet.items.length > 0) {
                                    // No cancel in scavenging
                                    $('pagemaintitletext').innerHTML = _("You must choose one card to save under Galactic Scavengers");
                                    dojo.style('action_nothing_to_play', 'display', 'none');
                                }
                            } else {
                                if ((this.immediateAlternatives === null || this.immediateAlternatives.length === 0)
                                        && this.paymentNeedsConfirm()) {
                                    this.addActionButton('payment_confirm', _("Done"), 'onPaymentConfirm');
                                    if (this.paymentCost > 0 && !this.isMilitarySettle) {
                                        dojo.query('#payment_confirm').addClass('disabled');
                                    }
                                }
                                this.addActionButton('action_cancel_payment', _("Cancel"), 'onDontPay');
                            }
                        }
                        break;
                    case 'consume':
                    case 'exploreconsume':
                        if (this.isCurrentPlayerActive()) {
                            // Prestige Trade gives a consume cards for VP option
                            if (dojo.indexOf(args['prestigeTrade'], this.player_id) != -1) {
                                this.addActionButton('action_consumeCards', _("Consume cards"), 'onConsumeCards');
                            }
                            this.addActionButton('action_stopConsumption', _("Pass"), 'onStopConsumption');
                            if (this.exploreSet.items.length > 0) {
                                dojo.style('action_stopConsumption', 'display', 'none');
                                this.lastPaymentTitle = $('pagemaintitletext').innerHTML;
                                $('pagemaintitletext').innerHTML = _("You must choose one card to keep");
                            }
                        }
                        break;
                    case 'productionwindfall':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_noproduction', _("Pass"), 'onNoWindfallProduction');
                        }
                        break;

                    case 'initialDiscardHomeWorld':
                        if (this.isCurrentPlayerActive()) {
                            dojo.addClass('hand_panel', 'paymentMode');
                            if (this.initialDiscardNeedsConfirm()) {
                                this.addActionButton('initial_discard_home_confirm', _("Done"), 'onInitialDiscardHomeConfirm');
                                dojo.query('#initial_discard_home_confirm').addClass('disabled');
                            }
                            this.checkInitialDiscardHomeArm();
                        }
                        break;

                    case 'initialDiscard':
                        if (this.isCurrentPlayerActive()) {
                            if (this.initialDiscardNeedsConfirm()) {
                                this.addActionButton('initial_discard_confirm', _("Done"), 'onInitialDiscardConfirm');
                                dojo.query('#initial_discard_confirm').addClass('disabled');
                            }
                            this.checkInitialDiscardArm();
                        }
                    case 'initialDiscardScavenger':
                    case 'endrounddiscard':
                    case 'developdiscard':
                    case 'settlediscard':
                        if (this.isCurrentPlayerActive()) {
                            dojo.addClass('hand_panel', 'paymentMode');
                        }
                        break;

                    case 'takeover_attackerboost':
                    case 'takeover_defenderboost':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('tmpboost_done', _("I'm ready now"), 'onNoMoreBoost');
                        }
                        break;

                    case 'searchAction':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('searchAction_1', _("Developments that provide +1 or +2 military"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_2', _("1 or 2 defense military windfall worlds"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_3', _("1 or 2 cost windfall worlds"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_4', _("Worlds with a chromosome symbol"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_5', _("Alien good worlds"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_6', _("Cards with consume powers with 2+ goods"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_7', _("Military worlds with 5+ defense"), 'onSearchActionChoose');
                            this.addActionButton('searchAction_8', _("6-cost &lt;?&gt; developments"), 'onSearchActionChoose');
                            if (this.gamedatas.takeovers != false) {
                                this.addActionButton('searchAction_9', _("Cards with takeover powers"), 'onSearchActionChoose');
                            }
                        }
                        break;
                    case 'searchActionChoose':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('searchActiok_1', _("Keep"), 'onSearchActionChooseK');
                            this.addActionButton('searchActiok_0', _("Discard"), 'onSearchActionChooseK');
                        }
                        break;
                    case 'takeover_maydefeat':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('defeat_0', _("Do nothing"), 'onDefeatTakeover');
                            this.addActionButton('defeat_1', _("Defeat takeover"), 'onDefeatTakeover');
                        }
                        break;
                    case 'discardtoputgood':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_nothing_to_play', _("I won't"), 'onNothingToPlay');
                            dojo.addClass('hand_panel', 'paymentMode');
                        }
                        break;
                    case 'invasionGameResolution':
                        if (this.isCurrentPlayerActive()) {
                            if (this.gamedatas.players[this.player_id].bunker_used == 0) {
                                this.addActionButton('action_bunker', _("Bunker power"), 'onBunkerPower');
                                this.addTooltip('action_bunker', '', _("Discard 1 card to get a temporary +2 against Xeno"));
                            }
                            this.addActionButton('action_xeno_defeat', _("Do not repulse Xenos"), 'onDoNotRepulse');
                        }
                        break;
                    case 'warEffort':
                        if (this.isCurrentPlayerActive()) {
                            this.addActionButton('action_nothing_to_play', _("Pass"), 'onNothingToPlay');
                        }
                        break;
                }


                if (!this.isCurrentPlayerActive()) {
                    dojo.removeClass('hand_panel', 'paymentMode');
                    dojo.removeClass('tableau_' + this.player_id, 'paymentMode');
                }
            },



            /////////////////////////////////////////////////////////////////:
            ////////////////////// UI events

            onHandleImmediateAlternative: function (alternative) {
                var call_options = {
                        lock: true,
                        card: this.nextCardToPlay.id,
                };
                switch (alternative.kind) {
                case 'military':
                    call_options.money = '';
                    call_options.mode = 'military';
                    break;
                case 'pay':
                    if (!this.checkCurrentPayment(/* execute = */ true)) {
                        this.onDontPay();
                    }
                    call_options = null;
                    break;
                case 'rdcrashprogram':
                    call_options.rdcrashprogram = alternative.card_id;
                    call_options.money = '';
                    call_options.mode = 'pay';
                    break;
                case 'colonyship':
                    call_options.colonyship = alternative.card_id;
                    call_options.money = '';
                    call_options.mode = 'pay';
                    break;
                }
                if (call_options !== null) {
                    this.playCardAndPayMaybeOortCloud(this.nextCardToPlay.type, call_options);
                }
            },

            onBunkerPower: function() {
                var cards = this.playerHand.getSelectedItems();

                if (cards.length != 1) {
                    this.showMessage(_("You must select exactly one card to discard to get a +2 Defense against Xeno"), 'error');
                } else {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/bunker.html", {
                        lock: true,
                        card: cards[0].id
                    }, this, function() {}, function() {
                        this.playerHand.unselectAll();
                        dojo.destroy('action_bunker');
                    });
                }
            },

            onDoNotRepulse: function() {
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/xenoDonotrepulse.html", {
                    lock: true
                }, this, function() {}, function() {});
            },

            onPlayerHandSelectionChanged: function() {
                console.log('onPlayerHandSelectionChanged');

                var cards = this.playerHand.getSelectedItems();
                var card_ids;
                var i;
                if (cards.length > 0 || dojo.query('#tableau_' + this.player_id + ' .selectedGood.good3').length > 0 || dojo.query('#tableau_' + this.player_id + ' .selectedGood.good2').length > 0) {
                    if (this.checkAction("initialdiscard", true)) {
                        if (this.gamedatas.gamestate.name == "initialDiscardHomeWorld") {
                            this.checkInitialDiscardHomeArm();
                        } else if (this.gamedatas.gamestate.name == "initialDiscard") {
                            this.checkInitialDiscardArm();
                        } else if (this.gamedatas.gamestate.name == "initialDiscardAncientRace") {
                            // In this case no confirmation button exists
                            this.checkInitialDiscardArm(/*instant_execute = */ true);
                        } else {
                            console.error("Invalid state for initialdiscard action");
                        }
                    } else if (this.checkAction("initialdiscardScavenger", true)) {
                        // Initial discard: choose 1 cards to discard
                        var to_discard = 1;

                        if (cards.length == to_discard) {
                            card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }

                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/initialdiscard.html", {
                                lock: true,
                                cards: card_ids
                            }, this, function() {}, function() {
                                this.playerHand.unselectAll();
                            });
                        }
                    } else if (this.checkAction('replaceWorld', true)) {
                        if (cards.length == 1 && dojo.query('.selectedCard').length > 0) {
                            var replace_world_id = dojo.query('.selectedCard')[0].id.substr(5);
                            // Oort Cloud is handled implicitly by selecting matching color
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html", {
                                lock: true,
                                card: cards[0].id,
                                settlereplace: replace_world_id,
                                money: ''
                            }, this, function() {}, function(is_error) {
                                if (is_error) {
                                    this.playerHand.unselectAll();
                                    dojo.query('.selectedCard').removeClass('selectedCard');
                                }
                            });
                        }
                    } else if (this.checkAction("endrounddiscard", true)) {
                        if (cards.length == this.gamedatas.gamestate.args[this.player_id]) {
                            card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/endRoundDiscard.html", {
                                lock: true,
                                cards: card_ids
                            }, this, function() {}, function() {
                                this.playerHand.unselectAll();
                            });
                        }
                    } else if (this.checkAction("developdiscard", true)) {
                        if (cards.length == this.gamedatas.gamestate.args[this.player_id]) {
                            card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }
                            if (cards.length == 1) {
                                var card_type_id = this.playerHand.getItemTypeById(cards[0].id);
                                var card_type = this.gamedatas.card_types[card_type_id];
                                if (card_type.type == "development") {
                                    var card_name = card_type.name;
                                    this.confirmationDialog(dojo.string.substitute(_("Do you really want to discard ${card_name}?"), {
                                        card_name: card_name
                                    }), dojo.hitch(this, function() {
                                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/developdiscard.html", {
                                            lock: true,
                                            cards: card_ids
                                        }, this, function() {}, function() {});
                                    }));
                                    this.playerHand.unselectAll();
                                } else {
                                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/developdiscard.html", {
                                        lock: true,
                                        cards: card_ids
                                    }, this, function() {}, function() {
                                        this.playerHand.unselectAll();
                                    });
                                }
                            } else {
                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/developdiscard.html", {
                                    lock: true,
                                    cards: card_ids
                                }, this, function() {}, function() {
                                    this.playerHand.unselectAll();
                                });
                            }
                        }
                    } else if (this.checkAction("settlediscard", true)) {
                        if (cards.length == this.gamedatas.gamestate.args[this.player_id]) {
                            card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/settlediscard.html", {
                                lock: true,
                                cards: card_ids
                            }, this, function() {}, function() {
                                this.playerHand.unselectAll();
                            });
                        }
                    } else if (this.checkAction("discardToPutGood", true)) {
                        if (cards.length == 1) {
                            card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/discardToPutGood.html", {
                                lock: true,
                                cards: card_ids
                            }, this, function() {}, function() {
                                this.playerHand.unselectAll();
                            });
                        }
                    } else if (this.checkAction("exploreCardChoice", true)) {
                        if (this.isCurrentPlayerActive() && this.playerHasExploreMix()) {
                            this.checkExploreArm();
                        }
                    } else if (this.checkAction('develop', true) || this.checkAction('settle', true) || this.checkAction('militaryboost', true)) {
                        if (!this.paymentMode && !this.scavenging) {
                            if (dojo.query('.mercenarySelected').length == 0) {

                                if (this.checkAction('develop', true) || this.checkAction('settle', true)) {
                                    // Selection of 'nextCardToPlay'
                                    if (cards.length == 1) {
                                        var card_id = cards[0].id;
                                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCard.html", {
                                            lock: true,
                                            card: card_id
                                        }, this, function() {}, function(is_error) {
                                            if (is_error) {
                                                this.playerHand.unselectAll();
                                            }
                                        });
                                    }
                                }
                            } else {
                                // Card to discard for temporary military boost
                                if (cards.length == 1) {
                                    var card_id = cards[0].id;

                                    var mercenary_card_id = dojo.query('.mercenarySelected')[0].id.substr(5);

                                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/militarytactics.html", {
                                        lock: true,
                                        card: mercenary_card_id,
                                        discard: card_id
                                    }, this, function() {}, function() {});
                                }
                            }
                        } else {
                            // Selection of cards needed for the payment of 'nextCardToPlay'
                            if (this.immediateAlternatives !== null && this.immediateAlternatives.length > 0) {
                                if (this.checkCurrentPayment(/* execute = */ false)) {
                                    dojo.query('#btn_immediate_alternative_pay').removeClass('disabled');
                                } else {
                                    dojo.query('#btn_immediate_alternative_pay').addClass('disabled');
                                }
                            } else {
                                this.checkPaymentArm();
                            }
                        }
                    } else if (this.checkAction('draft', true)) {
                        var card_id = cards[0].id;
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/draft.html", {
                            lock: true,
                            card: card_id
                        }, this, function() {}, function(is_error) {
                            if (is_error) {
                                this.playerHand.unselectAll();
                            }
                        });

                    }
                } else {
                    if (this.immediateAlternatives !== null && this.immediateAlternatives.length > 0) {
                        if (this.paymentCost > 0) {
                            dojo.query('#btn_immediate_alternative_pay').addClass('disabled');
                        } else {
                            dojo.query('#btn_immediate_alternative_pay').removeClass('disabled');
                        }
                    } else {
                        this.checkPaymentArm();
                    }
                }
            },

            // Check current payment in the interface and return true if payment matches the cost
            // Optionally send payment to server if `execute` is true
            checkCurrentPayment: function(execute = true) {
                if (!this.paymentMode && this.immediateAlternatives === null) {
                    return false;
                }
                if (this.isMilitarySettle) {
                    if (execute) {
                        this.paymentMode = false;
                        dojo.removeClass('hand_panel', 'paymentMode');
                        dojo.removeClass('hand_panel', 'paymentModeScavenger');
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html",
                                      {
                                          lock: true,
                                          card: this.nextCardToPlay.id,
                                          money: '',
                                          goods: '',
                                          rdcrashprogram: null,
                                          arts: '',
                                          scavenger: null,
                                          mode: 'military',
                                      }, this, function() {}, function(is_error) {
                                          if (is_error) {
                                              this.onDontPay();
                                          }});
                    }
                    // The check of military strength was done prior to the
                    // cardcost notification
                    return true;
                }

                var cards = this.playerHand.getSelectedItems();
                var card_ids;
                var i;

                var paid = 0;

                // Note : good_for_settlecost
                var goods = dojo.query('#tableau_' + this.player_id + ' .selectedGood.good3');
                if (goods.length > 0) {
                    paid += 3 * goods.length;
                }

                // Note : good for devcost
                var goods = dojo.query('#tableau_' + this.player_id + ' .selectedGood.good2');
                if (goods.length > 0) {
                    paid += 2 * goods.length;
                }

                // Note : R&D crash program
                var rdcrashprogram = dojo.query('#tableau_' + this.player_id + ' .rdcrashprogramSelected');
                if (rdcrashprogram.length > 0) {
                    paid += 3;
                }

                // Note : artifact for devcost
                var arts_ids = '';
                if (this.playerHandArt !== null) {
                    var arts = this.playerHandArt.getSelectedItems();
                    for (var i in arts) {
                        var art = arts[i];
                        if (art.type == 7) // dev -2
                        {
                            paid += 2;
                            arts_ids += art.id + ';';
                        } else if (art.type == 1) // world -2
                        {
                            paid += 2;
                            arts_ids += art.id + ';';
                        } else if (art.type == 4) // genes world -2
                        {
                            paid += 2;
                            arts_ids += art.id + ';';
                        } else if (art.type == 10) // dev -3
                        {
                            paid += 3;
                            arts_ids += art.id + ';';
                        } else if (art.type == 13) // world -3
                        {
                            paid += 3;
                            arts_ids += art.id + ';';
                        }
                    }
                }

                // allow overpayment via materials
                paid = Math.min(paid, this.paymentCost);
                paid += cards.length;

                if (paid == this.paymentCost) {
                    if (execute) {
                        // Play the card, for real
                        card_ids = '';
                        for (i in cards) {
                            card_ids += cards[i].id + ';';
                        }
                        var goods = dojo.query('#tableau_' + this.player_id + ' .selectedGood');
                        good_ids = '';
                        for (i in goods) {
                            if (goods[i].id) {
                                good_ids += goods[i].id.substr(5) + ';';
                            }
                        }

                        if (rdcrashprogram.length > 0) {
                            rdcrashprogram = rdcrashprogram[0].id.substr(5);
                        } else {
                            rdcrashprogram = null;
                        }

                        var scavenger = dojo.query('.stockitem_selected.scavenger_selected');
                        if (scavenger.length == 1) {
                            // player_hand_item_XX
                            scavenger = scavenger[0].id.substr(17);
                        } else {
                            scavenger = null;
                        }

                        this.paymentMode = false;
                        dojo.removeClass('hand_panel', 'paymentMode');
                        dojo.removeClass('hand_panel', 'paymentModeScavenger');
                        const call_options = {
                            lock: true,
                            card: this.nextCardToPlay.id,
                            money: card_ids,
                            goods: good_ids,
                            rdcrashprogram: rdcrashprogram,
                            arts: arts_ids,
                            scavenger: scavenger,
                            mode: 'pay',
                        };
                        this.playCardAndPayMaybeOortCloud(this.nextCardToPlay.type, call_options);
                    }
                    return true;
                } else
                    return false;
            },

            onPhaseChoice: function(evt) {
                console.log('onPhaseChoice');
                evt.preventDefault();

                if (this.checkAction("choosePhase")) {
                    var phase = evt.currentTarget.id.substr(15);
                    var phase_id = 0;
                    var bonus_id = 0;
                    switch (toint(phase)) {
                        case 1:
                            phase_id = 1;
                            break;
                        case 2:
                            phase_id = 1;
                            bonus_id = 1;
                            break;
                        case 3:
                            phase_id = 2;
                            break;
                        case 4:
                            phase_id = 3;
                            break;
                        case 5:
                            phase_id = 4;
                            break;
                        case 6:
                            phase_id = 4;
                            bonus_id = 1;
                            break;
                        case 7:
                            phase_id = 5;
                            break;
                        case 8:
                            phase_id = 1;
                            bonus_id = 100;
                            break;
                        case 9:
                            phase_id = 5;
                            bonus_id = 3;
                            break;
                    }

                    var bCardBonus = this.prestige_action;

                    if (bCardBonus) {
                        this.gamedatas.gamestate.args.searchavail[this.player_id] = 0;
                        dojo.style('action_cancelphasebonus', 'display', 'none');
                        dojo.style('action_phasebonus', 'display', 'none');
                        dojo.style('action_search', 'display', 'none');
                        this.prestige_action = false;
                    }

                    this.playerHand.unselectAll();
                    this.onCancelPhaseBonus();
                    this.pending_phase_choice = {
                        phase: phase_id,
                        bonus: bonus_id,
                        cardbonus: bCardBonus
                    }

                    this.checkPhaseSelectArm()
                }
            },

            onPhaseCancel: function() {
                for (var phase_id in this.current_phase_choices) {
                    if (Object.hasOwn(this.current_phase_choices[phase_id], this.player_id)
                            && this.current_phase_choices[phase_id][this.player_id] >= 10
                            && this.current_phase_choices[phase_id][this.player_id] < 100) {
                        this.gamedatas.gamestate.args.searchavail[this.player_id] = 1;
                    }
                }
                if (this.pending_phase_choice != null && this.pending_phase_choice.cardbonus) {
                        this.gamedatas.gamestate.args.searchavail[this.player_id] = 1;
                }
                if (this.gamedatas.gamestate.name == "phaseChoiceCrystal") {
                    this.updatePhaseChoices(this.current_phase_choices);
                } else {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/cancelPhase.html", {
                        lock: true
                    }, this, function() {}, function() {});
                }
            },

            initialDiscardNeedsConfirm: function() {
                return this.bga.userPreferences.get(8).toString() != '2';
            },
            checkInitialDiscardArm: function(instant_execute = false) {
                console.log('checkInitialDiscardArm');

                instant_execute = instant_execute || (!this.initialDiscardNeedsConfirm());

                // Initial discard: choose 2 cards to discard (except if home world is Ancient Race)
                var to_discard = 2;
                if (dojo.query('#tableau_' + this.player_id + ' .card_type_107').length == 1) {
                    if (this.gamedatas.gamestate.name == "initialDiscard") {
                        to_discard = 3;
                    } else if (this.gamedatas.gamestate.name == "initialDiscardAncientRace") {
                        to_discard = 1;
                    } else {
                        console.error("Unhandled Ancient Race");
                    }
                }

                const cards = this.playerHand.getSelectedItems();
                if (cards.length == to_discard) {
                    if (instant_execute) {
                        this.onInitialDiscardConfirm();
                    } else {
                        dojo.query('#initial_discard_confirm').removeClass('disabled');
                    }
                } else {
                    dojo.query('#initial_discard_confirm').addClass('disabled');
                }
            },
            checkInitialDiscardHomeArm: function() {
                console.log('checkInitialDiscardHomeArm');

                const discard_hand = this.playerHand.getSelectedItems();
                const discard_world = dojo.query('.selectedCard');
                if (discard_hand.length == 2 && discard_world.length == 1) {
                    const instant_execute = !this.initialDiscardNeedsConfirm();

                    if (instant_execute) {
                        this.onInitialDiscardHomeConfirm();
                    } else {
                        dojo.query('#initial_discard_home_confirm').removeClass('disabled');
                    }
                } else {
                    dojo.query('#initial_discard_home_confirm').addClass('disabled');
                }
            },

            exploreKeepHowMany: function() {
                if (this.checkAction("gamble", true) || this.checkAction("scavenge", true)) {
                    return 1;
                } else {
                    return this.gamedatas.gamestate.args[this.player_id].keep;
                }
            },
            playerHasExploreMix: function() {
                return typeof(this.gamedatas.gamestate.args[this.player_id]) != 'undefined' && typeof(this.gamedatas.gamestate.args[this.player_id].mix) != 'undefined';
            },
            exploreKeepCards: function(cards) {
                var tokeep = '';
                for (var i in cards) {
                    tokeep += cards[i].id + ';';
                }
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/exploreCardChoice.html", {
                    lock: true,
                    tokeep: tokeep
                }, this, function() {}, function() {});
            },
            exploreDiscardCards: function(cards) {
                card_ids = '';
                for (i in cards) {
                    card_ids += cards[i].id + ';';
                }
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/exploreDiscard.html", {
                    lock: true,
                    cards: card_ids
                }, this, function() {}, function() {
                    this.playerHand.unselectAll();
                });
            },
            exploreNeedsConfirm: function() {
                var need_confirm = !this.checkAction("scavenge", true)
                                  && !this.checkAction("gamble", true)
                                  && this.bga.userPreferences.get(7).toString() != '3';
                if (!need_confirm) {
                    return false;
                }
                if (this.bga.userPreferences.get(7).toString() == '2') {
                    if (this.playerHasExploreMix()) {
                        var n = Math.max(0, this.gamedatas.gamestate.args[this.player_id].draw - this.gamedatas.gamestate.args[this.player_id].keep);
                    } else {
                        var n = this.exploreKeepHowMany();
                    }
                    if (n == 1) {
                        return false;
                    }
                }
                return true;
            },
            onExploredSelectionChanged: function() {
                console.log('onExploredSelectionChanged');
                this.checkExploreArm()
            },
            checkExploreArm: function() {
                console.log('checkExploreArm');

                if (this.playerHasExploreMix()) {
                    var cards = this.playerHand.getSelectedItems();
                    var n = Math.max(0, this.gamedatas.gamestate.args[this.player_id].draw - this.gamedatas.gamestate.args[this.player_id].keep);
                } else {
                    var cards = this.exploreSet.getSelectedItems();
                    var n = this.exploreKeepHowMany();
                }
                if (cards.length == n) {
                    const instant_execute = !this.exploreNeedsConfirm();
                    if (instant_execute) {
                        this.onExploreDone();
                    } else {
                        dojo.query('#action_explore_confirm').removeClass('disabled');
                    }
                } else {
                    dojo.query('#action_explore_confirm').addClass('disabled');
                }
            },
            onExploreDone: function() {
                if (typeof(this.gamedatas.gamestate.args[this.player_id]) != 'undefined' && typeof(this.gamedatas.gamestate.args[this.player_id].mix) != 'undefined') {
                    var mustDiscard = Math.max(0, this.gamedatas.gamestate.args[this.player_id].draw - this.gamedatas.gamestate.args[this.player_id].keep);
                    var cards = this.playerHand.getSelectedItems();
                    if (cards.length != mustDiscard) {
                        dojo.query('#action_explore_confirm').addClass('disabled');
                        this.showMessage("Invalid number of cards selected", 'error');
                    } else {
                        this.exploreDiscardCards(cards);
                    }
                } else {
                    var cards = this.exploreSet.getSelectedItems();
                    var tokeep = this.exploreKeepHowMany();
                    if (cards.length != tokeep) {
                        dojo.query('#action_explore_confirm').addClass('disabled');
                        this.showMessage("Invalid number of cards selected", 'error');
                    } else {
                        this.exploreKeepCards(cards);
                    }
                }
            },


            combinePhaseBonus: function(phase_id, first, second) {
                // copy of function in game.php
                //
                // We are in two player case, and 2 cards was played on the same phase
                // For phase 1 and 4 we do (sum + 1), the incerement signalling the double bonus
                // For phase 2 and 3, we need to know which phase prestige is applied to
                // 2 => 2 normal phases
                // 12 => first is prestige, second is normal
                // 22 => first is normal, second is prestige
                // For phase 1 and 4, there is only one phase so it doesn't matter if prestige is chosen for first or second
                // 2 for both bonuses
                // 12 for both bonuses and prestige
                // For phase 5 we too do (sum + 1) noting that the combination prestige+repair does not exist
                if (phase_id == 1 && (first == 100 || second == 100)) {
                    // + Orb case
                    return first + second + 1;
                } else if (phase_id == 2 || phase_id == 3) {
                    // note that the order of insertion is preserved so this is the second choice
                    return first + 2 + 2*second;
                } else {
                    if (first >= 10 || second >= 10) {
                        return 12;
                    } else if (phase_id == 5) {
                        return first + second + 1;
                    } else {
                        return 2;
                    }
                }
            },
            addPhaseChoice: function(current, pending) {
                const total_bonus = pending.bonus + (pending.cardbonus ? 10 : 0);
                if (pending.phase != 7 && this.player_id in current[pending.phase]) {
                    const number = parseInt(current[pending.phase][this.player_id]);
                    current[pending.phase][this.player_id] = this.combinePhaseBonus(
                        pending.phase, number, total_bonus).toString();
                } else {
                    current[pending.phase] = {[this.player_id]: total_bonus.toString()};
                }
                return current;
            },
            phaseSelectNeedsConfirm: function() {
                return this.bga.userPreferences.get(9).toString() != '2';
            },
            checkPhaseSelectArm: function() {
                // if last phase to choose, check for confirmation
                if (this.phaseSelectNeedsConfirm() && (this.numberPlayers() > 2 || this.phases_chosen > 0)) { // FIXME psi-crystal
                    var choices;
                    try {
                        choices = structuredClone(this.current_phase_choices);
                    } catch (e) {
                        if (e instanceof ReferenceError) {
                            // bail out if structuredClone is not available for now
                            this.onPhaseSelectConfirm();
                            return;
                        } else {
                            throw e;
                        }
                    }
                    this.updatePhaseChoices(this.addPhaseChoice(choices, this.pending_phase_choice));
                } else {
                    this.onPhaseSelectConfirm();
                }
            },
            onPhaseSelectConfirm: function() {
                var callback = function() {};
                if (this.pending_phase_choice.phase === 7) {
                    callback = function() {
                        this.showMessage(_("Your search action has been registered"), 'info');
                    }
                }
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/choosePhase.html", {
                    lock: true,
                    phase: this.pending_phase_choice.phase,
                    bonus: this.pending_phase_choice.bonus,
                    cardbonus: this.pending_phase_choice.cardbonus,
                }, this, function() {}, callback);
            },

            paymentNeedsConfirm: function() {
                return this.bga.userPreferences.get(10).toString() != '2';
            },
            checkPaymentArm: function() {
                if (this.checkCurrentPayment(/* execute = */ false)) {
                    if (this.paymentNeedsConfirm()) {
                        dojo.query('#payment_confirm').removeClass('disabled');
                    } else {
                        this.onPaymentConfirm();
                    }
                    return true;
                } else {
                    dojo.query('#payment_confirm').addClass('disabled');
                    return false;
                }
            },
            onPaymentConfirm: function() {
                if (!this.checkCurrentPayment(/* execute = */ true)) {
                    this.showMessage("WARNING: Payment failed. Please reload by pressing F5.", 'error');
                }
            },


            onNothingToPlay: function() {
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/nothingToPlay.html", {
                    lock: true
                }, this, function() {}, function() {});
            },

            // Cancel last card played
            onDontPay: function() {
                console.log("onDontPay");
                if (this.nextCardToPlay) {
                    this.playerHand.addToStockWithId(this.nextCardToPlay.type, this.nextCardToPlay.id, $('card_' + this.nextCardToPlay.id));
                    dojo.destroy($('card_wrapper_' + this.nextCardToPlay.id));
                    $('tableau_nbr_' + this.player_id).innerHTML = toint($('tableau_nbr_' + this.player_id).innerHTML) - 1;
                }

                if (this.playerHandArt !== null) {
                    this.playerHandArt.unselectAll();
                }

                // Unselect R&D crash program if it is selected
                arr_rad = dojo.query('#tableau_' + this.player_id + ' .rdcrashprogramSelected');
                if (arr_rad.length > 0) {
                    dojo.removeClass(arr_rad[0].id, 'rdcrashprogramSelected');
                }

                this.playerHand.unselectAll();
                dojo.query('.selectedGood').removeClass('selectedGood');
                this.paymentMode = false;
                dojo.removeClass('hand_panel', 'paymentMode');
                dojo.removeClass('hand_panel', 'paymentModeScavenger');
                this.nextCardToPlay = null;
                this.paymentCost = 0;
                this.immediateAlternatives = null;
                this.isMilitarySettle = false;
                dojo.empty('generalactions');
                this.updatePageTitle();
            },

            checkConsumptionAction: function() {
                // Check if 1 world + 1 good are selected
                var goods = dojo.query('.selectedGood');
                var cards = dojo.query('.selectedCard');

                // Check alien ress selected
                if (this.playerHandArt !== null) {
                    var selected = this.playerHandArt.getSelectedItems();
                    if (selected.length == 1) {
                        if (selected[0].type == 2 || selected[0].type == 9) {
                            goods = [{
                                id: 'good_artifact_' + selected[0].id
                            }];
                        }
                    }
                }

                if (goods.length == 1 && cards.length == 1) {
                    var world_id = cards[0].id.substr(5);
                    var good_id = goods[0].id.substr(5);

                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/consume.html", {
                        lock: true,
                        good: good_id,
                        world: world_id
                    }, this, function() {}, function() {});

                    // In any case, remove selection
                    dojo.query('.selectedGood').removeClass('selectedGood');
                    dojo.query('.selectedCard').removeClass('selectedCard');
                }

            },
            checkGoodForMilitaryAction: function() {
                // Check if 1 card + 1 good are selected
                var goods = dojo.query('.selectedGood');
                var cards = dojo.query('.selectedCard');

                // Check alien ress selected
                if (this.playerHandArt !== null) {
                    var selected = this.playerHandArt.getSelectedItems();
                    if (selected.length == 1) {
                        if (selected[0].type == 2 || selected[0].type == 9) {
                            goods = [{
                                id: 'good_artifact_' + selected[0].id
                            }];
                        }
                    }
                }

                if (goods.length == 1 && cards.length == 1) {
                    var card_id = cards[0].id.substr(5);
                    var good_id = goods[0].id.substr(5);

                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/goodForMilitary.html", {
                        lock: true,
                        good: good_id,
                        card: card_id
                    }, this, function() {}, function() {});

                    // In any case, remove selection
                    dojo.query('.selectedGood').removeClass('selectedGood');
                    dojo.query('.selectedCard').removeClass('selectedCard');
                }
            },
            onEndGoodDragging: function(item_id, left, top) {
                console.log("onEndGoodDragging, " + left + "x" + top);

                // good_<id>
                var good_id = item_id.substr(5);
                console.log("good id = " + good_id);

                if (!this.checkAction("consume", true)) {
                    this.slideToObject($('good_' + good_id), $('good_wrap_' + good_id)).play();
                    return;
                }

                var itemcoords = dojo.coords(item_id);
                console.log(itemcoords);
                var item_x = itemcoords.x + itemcoords.w / 2;
                var item_y = itemcoords.y + itemcoords.h / 2;

                // Go through all cards in player tableau to find the good one
                dojo.query('#tableau_' + this.player_id + ' .card').forEach(dojo.hitch(this, function(node) {
                    console.log(node);
                    var coords = dojo.coords(node);
                    console.log(coords);

                    if ((item_x >= coords.x && item_x <= (coords.x + coords.w)) &&
                        (item_y >= coords.y && item_y <= (coords.y + coords.h))) {
                        var world_id = node.id.substr(5);
                        console.log('Card found: ' + world_id);
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/consume.html", {
                            lock: true,
                            good: good_id,
                            world: world_id
                        }, this, function() {}, function(is_error) {

                            if (is_error) {
                                // In case of error: move back the good to initial location
                                this.slideToObject($('good_' + good_id), $('good_wrap_' + good_id)).play();
                            }
                        });

                        return;
                    }

                }));

                // No card found => back to initial location
                if ($('good_' + good_id)) {
                    this.slideToObject($('good_' + good_id), $('good_wrap_' + good_id)).play();
                }
            },

            onStopConsumption: function() {
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/stopConsumption.html", {
                    lock: true
                }, this, function() {}, function() {});
            },

            onClickOnGood: function(evt) {
                console.log('onClickOnGood');
                dojo.stopEvent(evt);

                var good_id = evt.currentTarget.id.substr(5);
                var power_good_for_military = dojo.query('#tableau_' + this.player_id + ' .power_good_for_military:not(.damaged)').length;
                var power_good_for_military_defense = dojo.query('#tableau_' + this.player_id + ' .power_good_for_military_defense:not(.damaged)').length;
                var power_good_for_devcost = dojo.query('#tableau_' + this.player_id + ' .power_good_for_devcost').length;
                var power_good_for_settlecost = dojo.query('#tableau_' + this.player_id + ' .power_good_for_settlecost').length;

                if (this.checkAction('sell', true)) {
                    // goodsell_<id>
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/sell.html", {
                        lock: true,
                        card: good_id
                    }, this, function() {}, function() {});
                } else if (this.checkAction('warEffort', true)) {
                    // goodsell_<id>
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/warEffort.html", {
                        lock: true,
                        card: good_id
                    }, this, function() {}, function() {});
                } else if (this.checkAction('selectGood', true)
                           || this.checkAction('militaryboost', true) && power_good_for_military
                           || this.checkAction('resolveInvasion', true) && power_good_for_military_defense
                           || this.checkAction('develop', true) && this.paymentMode && power_good_for_devcost
                           || this.checkAction('settle', true) && this.paymentMode && power_good_for_settlecost
                ) {
                    if (dojo.hasClass(evt.currentTarget.id, 'selectedGood')) {
                        dojo.removeClass(evt.currentTarget.id, 'selectedGood');
                    } else if (this.checkAction('consume', true)) {
                        dojo.query('.selectedGood').removeClass('selectedGood');
                        dojo.addClass(evt.currentTarget.id, 'selectedGood');
                        this.checkConsumptionAction();
                    } else if (this.checkAction('settle', true)
                               && this.paymentMode
                               && power_good_for_settlecost
                               && dojo.hasClass(evt.currentTarget, 'good3') // Only Genes can be selected
                    ) {
                        if (dojo.query('.selectedGood').length >= power_good_for_settlecost) {
                            dojo.query('.selectedGood').removeClass('selectedGood');
                        }
                        dojo.addClass(evt.currentTarget.id, 'selectedGood');
                        if (!this.checkPaymentArm()) {
                            this.showMessage(_("This good is selected for discard = you can use 3 less cards for your payment"), 'info');
                        }
                    } else if (this.checkAction('militaryboost', true) && power_good_for_military && !this.paymentMode
                               || this.checkAction('resolveInvasion', true) && power_good_for_military_defense) {
                        dojo.query('.selectedGood').removeClass('selectedGood');
                        dojo.addClass(evt.currentTarget.id, 'selectedGood');
                        this.checkGoodForMilitaryAction();
                    } else if (this.checkAction('develop', true)
                                && this.paymentMode
                                && power_good_for_devcost
                                && dojo.hasClass(evt.currentTarget, 'good2') // Only rare can be selected
                    ) {
                        dojo.query('.selectedGood').removeClass('selectedGood');
                        dojo.addClass(evt.currentTarget.id, 'selectedGood');
                        if (!this.checkPaymentArm()) {
                            this.showMessage(_("This good is selected for discard = you can use 2 less cards for your payment"), 'info');
                        }
                    } else if (this.checkAction('selectGood', true)) {
                        dojo.query('.selectedGood').removeClass('selectedGood');
                        dojo.addClass(evt.currentTarget.id, 'selectedGood');
                    }
                }
            },

            onPhaseBonus: function() {
                this.confirmationDialog(_("This can be done only once during the game and cost 1 prestige point, are you sure?"), dojo.hitch(this, function() {
                    this.prestige_action = true;
                    dojo.style('action_phasebonus', 'display', 'none');
                    dojo.style('action_search', 'display', 'none');
                    dojo.style('action_cancelphasebonus', 'display', 'inline');

                    dojo.query('.boosted').style('display', 'inline');
                    dojo.query('.normalbonus').style('display', 'none');

                    dojo.addClass('phasechoice_panel', 'searchavail');
                    this.addTooltipOnPhaseButtons(true);
                }));
            },

            onSearchAction: function() {
                this.confirmationDialog(_("This search action can be done only once during the game, are you sure?"), dojo.hitch(this, function() {

                    this.pending_phase_choice = {
                        phase: 7,
                        bonus: 0,
                        cardbonus: true
                    }
                    this.gamedatas.gamestate.args.searchavail[this.player_id] = 0;

                    this.checkPhaseSelectArm();
                    this.playerHand.unselectAll();
                    this.onCancelPhaseBonus();

                    dojo.style('action_phasebonus', 'display', 'none');
                    dojo.style('action_search', 'display', 'none');
                    dojo.style('action_cancelphasebonus', 'display', 'none');
                }));
            },

            onSearchActionChoose: function(evt) {
                // searchAction_<id>
                var action_id = evt.currentTarget.id.substr(13);

                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/search.html", {
                    lock: true,
                    category: action_id
                }, this, function() {}, function() {});

            },
            onSearchActionChooseK: function(evt) {
                // searchAction_<id>
                var action_id = evt.currentTarget.id.substr(13);

                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/searchchoose.html", {
                    lock: true,
                    actio: action_id
                }, this, function() {}, function() {});

            },

            onCancelPhaseBonus: function() {
                if (this.hasGamePrestige()) {
                    if (this.gamedatas.gamestate.args.searchavail[this.player_id] == 1) {
                        dojo.style('action_phasebonus', 'display', 'inline');
                        dojo.style('action_search', 'display', 'inline');
                    }
                    dojo.style('action_cancelphasebonus', 'display', 'none');

                    dojo.query('.boosted').style('display', 'none');
                    dojo.query('.normalbonus').style('display', 'inline');
                    dojo.removeClass('phasechoice_panel', 'searchavail');
                    this.addTooltipOnPhaseButtons(false);
                }
            },


            onNoWindfallProduction: function() {
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/noWindfallProduction.html", {
                    lock: true
                }, this, function() {}, function() {});
            },

            onInitialDiscardConfirm: function() {
                console.log('onInitialDiscardConfirm');

                const cards = this.playerHand.getSelectedItems();
                var card_ids = '';
                for (i in cards) {
                    card_ids += cards[i].id + ';';
                }

                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/initialdiscard.html", {
                    lock: true,
                    cards: card_ids
                }, this, function() {}, function() {
                    this.playerHand.unselectAll();
                });
            },
            onInitialDiscardHomeConfirm: function() {
                console.log('onInitialDiscardHomeConfirm');

                const cards = this.playerHand.getSelectedItems();
                var card_ids = '';
                for (var i in cards) {
                    card_ids += cards[i].id + ';';
                }
                dojo.removeClass('tableau_' + this.player_id, 'paymentMode');
                const start_world_id = dojo.query('.selectedCard')[0].id.substr(5);
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/initialdiscardhome.html", {
                    lock: true,
                    start_world: start_world_id,
                    cards: card_ids
                }, this, function() {}, function() {});
            },

            onNoMoreBoost: function() {
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/noMoreBoost.html", {
                    lock: true
                }, this, function() {}, function() {});
            },
            onDefeatTakeover: function(evt) {
                // defeat_<id>
                var choice = evt.currentTarget.id.substr(7);
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/defeatTakeover.html", {
                    lock: true,
                    choice: choice
                }, this, function() {}, function() {});
            },
            onClickOnOpponentCardOnTableau: function(evt) {
                console.log('onClickOnCardOnTableau');
                console.log(evt);

                evt.preventDefault();

                if (this.gamedatas.takeovers == false) {
                    this.showMessage(_("Takeovers have been disabled for this game"), 'error');
                    return;
                }


                // card_<id>

                if (this.checkAction('settle', true)) {
                    if (dojo.hasClass(evt.currentTarget.id, 'selectedCard')) {
                        dojo.removeClass(evt.currentTarget.id, 'selectedCard');
                    } else {
                        dojo.query('.selectedCard').removeClass('selectedCard');
                        dojo.addClass(evt.currentTarget.id, 'selectedCard');
                    }
                }
            },
            onRepairWorld: function(evt) {
                // damage_<id>
                var card_id = evt.currentTarget.id.substr(7);

                dojo.stopEvent(evt);

                if (this.checkAction('repairDamaged')) {
                    var cards = this.playerHand.getSelectedItems();
                    var discard_id = '';
                    if (cards.length === 2) {
                        var discard_id = cards[0].id + ';' + cards[1].id + ';';
                    }

                    var goods = dojo.query('#tableau_' + this.player_id + ' .selectedGood');
                    var good_id = 0;
                    if (goods.length == 1) {
                        // good_<id>
                        good_id = goods[0].id.substr(5);
                    }


                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/repairDamaged.html", {
                        lock: true,
                        card: card_id,
                        discard_id: discard_id,
                        good: good_id
                    }, this, function() {}, function() {});
                }
            },

            onClickOnCardOnTableau: function(evt) {
                console.log('onClickOnCardOnTableau');
                console.log(evt);

                evt.preventDefault();

                // card_<id>
                var card_id = evt.currentTarget.id.substr(5);
                var i;
                var bDoNotSelectThisCard = false;

                var card_type_id = this.card_to_type[card_id];

                if (this.checkAction('productionwindfall', true) && dojo.query('#good_place_' + card_id + ' .good_wrap').length == 0) {
                    // Production on windfall or Damaged Alien Factory

                    if (this.gamedatas.card_types[card_type_id].name == 'Damaged Alien Factory' || this.gamedatas.card_types[card_type_id].name == 'Alien Archives') {
                        var cards = this.playerHand.getSelectedItems();
                        if (cards.length === 0) {
                            this.showMessage(_("You must select a card to discard from your hand first"), 'error');
                        } else {
                            var discard_id = cards[0].id;
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/produceifdiscard.html", {
                                lock: true,
                                card: card_id,
                                discard: discard_id
                            }, this, function() {}, function() {});
                        }
                    } else {
                        var cards = this.playerHand.getSelectedItems();
                        var discard_id = null;
                        if (cards.length != 0) {
                            var discard_id = cards[0].id;
                        }

                        if (card_type_id == 220) {
                            this.getGoodChoice(_("Which good would you like to produce here?"), dojo.hitch(this, function(good) {
                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/windfallProduction.html", {
                                    lock: true,
                                    card: card_id,
                                    discard: discard_id,
                                    oort: good
                                }, this, function() {}, function() {});
                            }));
                        } else {
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/windfallProduction.html", {
                                lock: true,
                                card: card_id,
                                discard: discard_id
                            }, this, function() {}, function() {});
                        }
                    }
                }

                if (this.checkAction('initialdiscardhome', true)) {
                    if (dojo.hasClass(evt.currentTarget.id, 'selectedCard')) {
                        dojo.removeClass(evt.currentTarget.id, 'selectedCard');
                    } else {
                        dojo.query('.selectedCard').removeClass('selectedCard');
                        dojo.addClass(evt.currentTarget.id, 'selectedCard');
                    }
                    this.checkInitialDiscardHomeArm();
                }

                if (this.checkAction('replaceWorld', true)) {
                    if (dojo.hasClass(evt.currentTarget.id, 'selectedCard')) {
                        dojo.removeClass(evt.currentTarget.id, 'selectedCard');
                    } else {
                        var cards = this.playerHand.getSelectedItems();
                        if (cards.length == 1) {
                            // Oort Cloud is handled implicitly by selecting matching color
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html", {
                                lock: true,
                                card: cards[0].id,
                                settlereplace: card_id,
                                money: ''
                            }, this, function() {}, function(is_error) {
                                if (is_error) {
                                    this.playerHand.unselectAll();
                                    dojo.query('.selectedCard').removeClass('selectedCard');
                                }
                            });
                        } else {
                            dojo.query('.selectedCard').removeClass('selectedCard');
                            dojo.addClass(evt.currentTarget.id, 'selectedCard');
                        }
                    }
                }

                if (this.checkAction('chooseDamage', true)) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/chooseDamage.html", {
                        lock: true,
                        card: card_id
                    }, this, function() {}, function() {});
                }

                //// Specific card actions

                if (this.gamedatas.card_types[card_type_id].name == 'Colony Ship'
                    || this.gamedatas.card_types[card_type_id].name == 'Doomed World'
                ) {
                    // Colony ship power
                    if (this.checkAction('settle', true)) {
                        if (this.paymentMode === false) {
                            this.showMessage(_("You must select a world first"), 'error');
                        } else {
                            this.paymentMode = false;
                            // Cannot be Oort Cloud in this branch
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html", {
                                lock: true,
                                card: this.nextCardToPlay.id,
                                colonyship: card_id,
                                money: '',
                                mode: 'pay',
                            }, this, function() {}, function(is_error) {
                                if (is_error) {
                                    this.onDontPay();
                                }
                            });
                        }
                    }
                } else if (this.gamedatas.card_types[card_type_id].name == 'Imperium Invasion Fleet') {
                    // Trigger a cloaking
                    if (this.paymentMode === false) {
                        this.showMessage(_("You must first choose a non-military world to conquer from your hand"), 'error');
                    } else {
                        this.paymentMode = false;
                        // Cannot be Oort Cloud in this branch
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html", {
                            lock: true,
                            card: this.nextCardToPlay.id,
                            cloaking: card_id,
                            money: '',
                            mode: 'military',
                        }, this, function() {}, function(is_error) {
                                if (is_error) {
                                    this.onDontPay();
                                }
                            });

                    }

                } else if (this.gamedatas.card_types[card_type_id].name == 'Imperium Cloaking Technology'
                           || this.gamedatas.card_types[card_type_id].name == 'Rebel Sneak Attack'
                ) {
                    // Must select a card from hand (a civil card to conquer as a military)

                    if (this.checkAction('settle', true)) {
                        // Have we select an opponent card?
                        if (dojo.query('.selectedCard').length == 1) {
                            // Trigger a takeover
                            if (this.checkAction('onlymilitarysettle', true)) {
                                this.showMessage(_("This power cannot be combined with a takeover power"), 'error');
                            } else {
                                var target_id = dojo.query('.selectedCard')[0].id.substr(5);
                                this.paymentMode = false;
                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/takeover.html", {
                                    lock: true,
                                    card: card_id,
                                    target: target_id,
                                    confirmed: false
                                }, this, function() {}, function(is_error) {
                                    if (is_error) {
                                        this.onDontPay();
                                    }
                                });
                            }
                        } else if (this.gamedatas.card_types[card_type_id].name == 'Imperium Cloaking Technology') {
                            // Trigger a cloaking
                            if (this.paymentMode === false) {
                                this.showMessage(_("You must first choose an opponent world to takeover OR a non-military world to conquer from your hand"), 'error');
                            } else {
                                this.paymentMode = false;
                                // Cannot be Oort Cloud in this branch
                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html", {
                                    lock: true,
                                    card: this.nextCardToPlay.id,
                                    cloaking: card_id,
                                    money: '',
                                    mode: 'military',
                                }, this, function() {}, function(is_error) {
                                    if (is_error) {
                                        this.onDontPay();
                                    }
                                });

                            }
                        } else if (this.gamedatas.card_types[card_type_id].name == 'Rebel Sneak Attack') {
                            this.showMessage(_("You must first choose an opponent world to takeover"), 'error');
                        }
                    }
                } else if ((this.gamedatas.card_types[card_type_id].name == 'Imperium Seat'
                            || this.gamedatas.card_types[card_type_id].name == 'Rebel Alliance'
                            || this.gamedatas.card_types[card_type_id].name == 'Interstellar Casus Belli'
                            || this.gamedatas.card_types[card_type_id].name == 'Imperium Planet Buster'
                           ) && this.checkAction('settle', true)) {
                    // Have we select an opponent card?
                    if (dojo.query('.selectedCard').length == 1) {
                        // Trigger a takeover
                        if (this.checkAction('onlymilitarysettle', true)) {
                            this.showMessage(_("This power cannot be combined with a takeover power"), 'error');
                        } else {
                            var target_id = dojo.query('.selectedCard')[0].id.substr(5);
                            this.paymentMode = false;
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/takeover.html", {
                                lock: true,
                                card: card_id,
                                target: target_id,
                                confirmed: false
                            }, this, function() {}, function(is_error) {
                                if (is_error) {
                                    this.onDontPay();
                                }
                            });
                        }
                    } else {
                        this.showMessage(_("You must first choose an opponent world to takeover"), 'error');
                    }

                } else if (this.gamedatas.card_types[card_type_id].name == 'R&D Crash Program' && this.checkAction('develop', true)) {
                    if (this.paymentMode === false) {
                        this.showMessage(_("You must first play a development from your hand"), 'error');
                    } else {
                        // Select or unselect this card
                        if (dojo.hasClass('card_' + card_id, 'rdcrashprogramSelected')) {
                            dojo.removeClass('card_' + card_id, 'rdcrashprogramSelected');
                        } else {
                            dojo.addClass('card_' + card_id, 'rdcrashprogramSelected');
                            if (!this.checkPaymentArm()) {
                                this.showMessage(_("You now have a cost reduction of 3 for this development."), 'info');
                            }
                        }
                    }
                } else if (this.gamedatas.card_types[card_type_id].name == 'New Military Tactics'
                           || this.gamedatas.card_types[card_type_id].name == 'Imperium Stealth Tactics'
                           || this.gamedatas.card_types[card_type_id].name == 'Anti-Xeno Militia' && this.checkAction('resolveInvasion', true)) {
                    // New Military Tactics power
                    if (this.checkAction('settle', true) || this.checkAction('militaryboost', true) || (this.checkAction('invasionResolution', true) && this.gamedatas.card_types[card_type_id].name == 'Anti-Xeno Militia')) {
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/militarytactics.html", {
                            lock: true,
                            card: card_id
                        }, this, function() {
                            this.showMessage(_("Your military gets +3 until the end of the phase"), 'info');
                        }, function() {});
                    }
                } else if (this.gamedatas.card_types[card_type_id].name == 'Alien Booby Trap') {
                    // Discard for tmp military bonus cards
                    if (this.checkAction('settle', true) || this.checkAction('militaryboost', true)) {
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/prestigeformilitary.html", {
                            lock: true,
                            card: card_id
                        }, this, function() {
                            this.showMessage(dojo.string.substitute(_("Your military has been upgraded (+3) until the end of the phase"), {}), 'info');
                        }, function() {});
                    }

                } else if (this.gamedatas.card_types[card_type_id].name == 'Space Mercenaries'
                           || this.gamedatas.card_types[card_type_id].name == 'Primitive Rebel World'
                           || this.gamedatas.card_types[card_type_id].name == 'Rebel Convict Mines'
                           || this.gamedatas.card_types[card_type_id].name == 'Mercenary Fleet'
                           || this.gamedatas.card_types[card_type_id].name == 'Uplift Mercenary Force'
                           || this.gamedatas.card_types[card_type_id].name == 'Rebel Troops'
                           || this.gamedatas.card_types[card_type_id].name == 'Anti-Xeno Defense Post'
                ) {
                    // Discard for tmp military bonus cards
                    if (this.checkAction('militaryboost', true) && (this.checkAction('resolveInvasion', true) || this.gamedatas.card_types[card_type_id].name != 'Anti-Xeno Defense Post')) {
                        if (this.paymentMode == true) {
                            // We must remove payment mode.
                            // (Otherwise, when having Rebel Cantina (ie : settle military as civil world), you can be in the situation where you are asked to discard cards
                            //  to settle the world while you'd like to use temporary military)

                            this.onDontPay();
                        }

                        var cards = this.playerHand.getSelectedItems();
                        if (cards.length > 0) {
                            card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }

                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/militarytactics.html", {
                                lock: true,
                                card: card_id,
                                discard: card_ids
                            }, this, function() {}, function() {});
                        } else {
                            // Select or unselect this card to go to "discard to boost military" mode
                            if (dojo.hasClass('card_' + card_id, 'mercenarySelected')) {
                                dojo.removeClass('card_' + card_id, 'mercenarySelected');
                                dojo.removeClass('hand_panel', 'paymentMode');
                                dojo.removeClass('hand_panel', 'paymentModeScavenger');
                            } else {
                                dojo.query('.mercenarySelected').removeClass('mercenarySelected');
                                dojo.addClass('card_' + card_id, 'mercenarySelected');
                                dojo.addClass('hand_panel', 'paymentMode');
                                this.showMessage(_("Discard one card to boost your military force until the end of the phase"), 'info');
                            }
                        }
                    }
                } else if (this.gamedatas.card_types[card_type_id].name == 'Pan-Galactic Hologrid'
                           || this.gamedatas.card_types[card_type_id].name == 'Alien Departure Point') {
                    // Note : consumecard on phase I

                    if (this.checkAction('exploreconsume', true)) {
                        var cards = this.playerHand.getSelectedItems();

                        var to_discard = this.gamedatas.card_types[card_type_id].powers[1][0].arg.repeat;
                        var min_to_discard = 1;
                        if (typeof this.gamedatas.card_types[card_type_id].powers[1][0].arg.inputfactor != 'undefined') {
                            min_to_discard = this.gamedatas.card_types[card_type_id].powers[1][0].arg.inputfactor;
                            to_discard = min_to_discard;
                        }

                        if (cards.length === 0 || cards.length > to_discard) {
                            this.showMessage(dojo.string.substitute(_("You must select up to ${nbr} cards to discard in your hand first"), {
                                nbr: to_discard
                            }), 'error');
                            bDoNotSelectThisCard = true;
                        } else if (cards.length < min_to_discard) {
                            bDoNotSelectThisCard = true;
                            this.showMessage(dojo.string.substitute(_("You must select at least ${nbr} cards to discard from your hand first"), {
                                nbr: min_to_discard
                            }), 'error');
                        } else {
                            var card_ids = '';
                            for (i in cards) {
                                card_ids += cards[i].id + ';';
                            }
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/consumecard.html", {
                                lock: true,
                                consumecard_card_id: card_id,
                                cards: card_ids
                            }, this, function() {}, function() {
                                this.playerHand.unselectAll();
                                dojo.query('.selectedCard').removeClass('selectedCard');
                            });
                        }
                    }

                } else if (this.gamedatas.card_types[card_type_id].name == 'Wormhole Prospectors' && this.checkAction('settle', true)) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/wormhole.html", {
                        lock: true
                    }, this, function() {}, function() {});
                } else if (this.checkAction('consume', true) &&
                    (
                        this.gamedatas.card_types[card_type_id].name == 'Deficit Spending' ||
                        this.gamedatas.card_types[card_type_id].name == 'Merchant World' ||
                        this.gamedatas.card_types[card_type_id].name == 'Novelty Peddlers' ||
                        this.gamedatas.card_types[card_type_id].name == 'Anti-Xeno Embassy' ||
                        this.gamedatas.card_types[card_type_id].name == 'Galactic Home Front' ||
                        this.gamedatas.card_types[card_type_id].name == 'Galactic Bazaar' ||
                        this.gamedatas.card_types[card_type_id].name == 'Galactic Bankers' ||
                        this.gamedatas.card_types[card_type_id].name == 'R&D Crash Program' ||
                        this.gamedatas.card_types[card_type_id].name == 'Pan-Galactic Security Council' ||
                        this.gamedatas.card_types[card_type_id].name == 'Alien Guardian' ||
                        this.gamedatas.card_types[card_type_id].name == 'Terraforming Colony' && dojo.query('.selectedGood').length == 0 ||
                        this.gamedatas.card_types[card_type_id].name == 'Wormhole Prospectors'
                    )
                ) {
                    var cards = this.playerHand.getSelectedItems();

                    var to_discard = this.gamedatas.card_types[card_type_id].powers[4][0].arg.repeat;
                    var min_to_discard = 1;
                    if (typeof this.gamedatas.card_types[card_type_id].powers[4][0].arg.inputfactor != 'undefined') {
                        min_to_discard = this.gamedatas.card_types[card_type_id].powers[4][0].arg.inputfactor;
                        to_discard = min_to_discard;
                    }

                    if (cards.length === 0 || cards.length > to_discard) {
                        this.showMessage(dojo.string.substitute(_("You must select up to ${nbr} cards to discard in your hand first"), {
                            nbr: to_discard
                        }), 'error');
                        bDoNotSelectThisCard = true;
                    } else if (cards.length < min_to_discard) {
                        bDoNotSelectThisCard = true;
                        this.showMessage(dojo.string.substitute(_("You must select at least ${nbr} cards to discard from your hand first"), {
                            nbr: min_to_discard
                        }), 'error');
                    } else {
                        var card_ids = '';
                        for (i in cards) {
                            card_ids += cards[i].id + ';';
                        }
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/consumecard.html", {
                            lock: true,
                            consumecard_card_id: card_id,
                            cards: card_ids
                        }, this, function() {}, function() {
                            this.playerHand.unselectAll();
                            dojo.query('.selectedCard').removeClass('selectedCard');
                        });
                    }
                } else if (this.gamedatas.card_types[card_type_id].name == 'Gambling World'
                           && (this.gamedatas.expansion == 3 || this.gamedatas.expansion == 4)
                           && this.checkAction('consume', true)) {
                    // RvI Gambling
                    var cards = this.playerHand.getSelectedItems();
                    if (dojo.query('#tableau_' + this.player_id + ' .selectedGood').length > 0) {
                        // Consuming a good
                    } else if (cards.length != 1) {
                        this.showMessage(_("You must select a card from your hand to ante first"), 'error');
                    } else {
                        // Gambling
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/rviGambling.html", {
                            lock: true,
                            ante_card_id: cards[0].id
                        }, this, function() {}, function() {
                            this.playerHand.unselectAll();
                            dojo.query('.selectedCard').removeClass('selectedCard');
                        });
                    }
                } else if ((this.gamedatas.card_types[card_type_id].name == 'Interstellar Casus Belli'
                            || this.gamedatas.card_types[card_type_id].name == 'Galactic Power Brokers'
                           ) && this.checkAction('consume', true)
                          ) {
                    // Consume prestige
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/consumeprestige.html", {
                        lock: true,
                        consumecard_card_id: card_id
                    }, this, function() {}, function() {
                        this.playerHand.unselectAll();
                        dojo.query('.selectedCard').removeClass('selectedCard');
                    });

                } else if (this.gamedatas.card_types[card_type_id].name == 'Golden Age of Terraforming'
                           || this.gamedatas.card_types[card_type_id].name == 'Lifeforms, inc.'
                          ) {
                    if (this.checkAction('develop', true) && this.gamedatas.card_types[card_type_id].name == 'Golden Age of Terraforming' ||
                        this.checkAction('settle', true)) {
                        if (this.paymentMode === false) {
                            if (this.gamedatas.gamestate.name == "settle") {
                                this.showMessage(_("You must select a world first"), 'error');
                            } else {
                                this.showMessage(_("You must select a development first"), 'error');
                            }
                        } else {
                            this.showMessage(_("Please directly select the good you'd like to use for the cost reduction."), 'info');
                        }
                    }
                } else if (this.checkAction('militaryboost', true) && dojo.query('#tableau_' + this.player_id + ' .power_good_for_military:not(.damaged)').length
                           || this.checkAction('resolveInvasion', true) && dojo.query('#tableau_' + this.player_id + ' .power_good_for_military_defense:not(.damaged)').length
                          ) {
                    // Select this card
                    if (dojo.hasClass(evt.currentTarget.id, 'selectedCard')) {
                        dojo.removeClass(evt.currentTarget.id, 'selectedCard');
                    } else {
                        dojo.query('.selectedCard').removeClass('selectedCard');
                        dojo.addClass(evt.currentTarget.id, 'selectedCard');
                        this.checkGoodForMilitaryAction();
                    }
                } else if (this.gamedatas.card_types[card_type_id].name == 'Alien Oort Cloud Refinery'
                           && (!this.checkAction('productionwindfall', true)
                               || dojo.query('#good_place_' + card_id + ' .good_wrap').length > 0)
                          ) {
                    bDoNotSelectThisCard = true;
                    dojo.style('oort_help', 'display', 'none');

                    // Choose Oort kind
                    this.getGoodChoice(_("Which kind do you want this world to be?"), dojo.hitch(this, function(kind) {
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/changeOortType.html", {
                            lock: true,
                            kind: kind
                        }, this, function() {}, function() {});
                    }));
                } else if (this.gamedatas.card_types[card_type_id].name == 'Galactic Scavengers') {
                    // Toggle the display of the Scavenger Panel to view which cards are currently saved under GS
                    bDoNotSelectThisCard = true;
                    if (dojo.style($('scavenger_panel'), 'display') == 'none' && this.scavengerSet.items.length > 0) {
                        dojo.style($('scavenger_panel'), 'display', 'block');
                    } else {
                        dojo.style($('scavenger_panel'), 'display', 'none');
                    }
                }

                if (this.checkAction('consume', true)) {
                    // Select this world for consumption
                    if (dojo.hasClass(evt.currentTarget.id, 'selectedCard') || bDoNotSelectThisCard) {
                        dojo.removeClass(evt.currentTarget.id, 'selectedCard');
                    } else {
                        dojo.query('.selectedCard').removeClass('selectedCard');
                        dojo.addClass(evt.currentTarget.id, 'selectedCard');
                        this.checkConsumptionAction();
                    }
                }

            },

            onGambling: function(evt) {
                dojo.stopEvent(evt);

                // gambling_X
                var number = evt.currentTarget.id.substr(9);
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/gambling.html", {
                    lock: true,
                    gambling: number
                }, this, function() {}, function() {});

            },

            // Prestige Trade bonus : consume up to 2 cards for VP
            onConsumeCards: function(evt) {
                dojo.stopEvent(evt);
                if (!this.checkAction('consume', true)) {
                    return;
                }

                var cards = this.playerHand.getSelectedItems();

                var to_discard = 2;

                if (cards.length === 0 || cards.length > to_discard) {
                    this.showMessage(dojo.string.substitute(_("You must select up to ${nbr} cards to discard in your hand first"), {
                        nbr: to_discard
                    }), 'error');
                    return;
                }

                var card_ids = '';
                for (var i in cards) {
                    card_ids += cards[i].id + ';';
                }
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/consumecard.html", {
                    lock: true,
                    cards: card_ids
                }, this, function() {}, function() {
                    this.playerHand.unselectAll();
                    dojo.destroy('action_consumeCards');
                });
            },

            onEndMilitaryDiscard: function(evt) {
                dojo.stopEvent(evt);

                // Unselect current card

                dojo.query('.mercenarySelected').removeClass('mercenarySelected');
                dojo.query('.consumeformilitarySelected').removeClass('consumeformilitarySelected');
                dojo.removeClass('hand_panel', 'paymentMode');
                dojo.removeClass('hand_panel', 'paymentModeScavenger');
                dojo.removeClass('tableau_panel_' + this.player_id, 'consume_for_military_mode');
            },

            drawForEachWorld: function(evt) {
                console.log('drawForEachWorld');
                console.log(evt);

                evt.preventDefault();

                var card_id = evt.currentTarget.id.substr(14);
                var card_type_id = this.card_to_type[card_id];
                var search;
                var min = 1;
                var msg = _("You have no ${kind} worlds");
                switch (card_type_id) {
                    case "30":
                        search = " .kind_3";
                        msg = dojo.string.substitute(msg, {
                            kind: this.gamedatas.good_types[3]
                        });
                        break;
                    case "308":
                        search = " .categ_military";
                        min = 2;
                        msg = _("You have no pair of Military worlds");
                        break;
                    case "310":
                        search = " .kind_2";
                        msg = dojo.string.substitute(msg, {
                            kind: this.gamedatas.good_types[2]
                        });
                        break;
                    case "311":
                        search = " .categ_xeno";
                        msg = dojo.string.substitute(msg, {
                            kind: _("Xeno Military")
                        });
                        break;
                }

                if (dojo.query("#tableau_panel_" + this.player_id + search + ":not(.damaged)").length >= min) {
                    dojo.destroy(evt.currentTarget.id);
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/drawForEachWorld.html", {
                        lock: true,
                        card: card_id
                    }, this, function() {}, function() {});
                } else {
                    this.showMessage(msg, 'error');
                }
            },

            drawForEachGood: function(evt) {
                console.log('drawForEachWorld');
                console.log(evt);

                evt.preventDefault();

                var card_id = evt.currentTarget.id.substr(14);
                var card_type_id = this.card_to_type[card_id];
                var msg = _("You haven't produced any ${good} during this phase");
                var bDraw;

                switch (card_type_id) {
                    case "7": // Diversified Economy
                        bDraw = this.gamedatas.produced_goods[0] > 0;
                        msg = _("You haven't produced any goods during this phase");
                        break;

                    case "9": // Research Labs
                        bDraw = this.gamedatas.produced_goods[4] > 0;
                        msg = dojo.string.substitute(msg, {
                            good: this.gamedatas.good_types[4]
                        });
                        break;

                    case "10": // Consumer Markets
                        bDraw = this.gamedatas.produced_goods[1] > 0;
                        msg = dojo.string.substitute(msg, {
                            good: this.gamedatas.good_types[1]
                        });
                        break;

                    case "263": // Imperium War Faction
                        bDraw = this.gamedatas.produced_goods[2] > 0;
                        msg = dojo.string.substitute(msg, {
                            good: this.gamedatas.good_types[2]
                        });
                        break;

                    case "307": // Galactic Home Front
                        bDraw = this.gamedatas.produced_goods[0] > 1;
                        msg = _("You haven't produced any pair of goods during this phase");
                        break;
                }

                if (bDraw) {
                    dojo.destroy(evt.currentTarget.id);
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/drawForEachGood.html", {
                        lock: true,
                        card: card_id
                    }, this, function() {}, function() {});
                } else {
                    this.showMessage(msg, 'error');
                }
            },

            /////////////// Orb play (Alien artifacts)

            setupNewOrbCard: function(card_div, card_type_id, div_id) {
                console.log("setupNewOrbCard");

                dojo.place(this.format_block('jtspl_orbcardhand', {
                    id: div_id
                }), card_div);
                dojo.connect($('rotcommand_' + div_id), 'onclick', this, 'onRotOrbCard');

                // Prevent the propagation of touchstart event which gets turned into a click by draggable
                $('rotcommand_' + div_id).addEventListener('touchstart', (e) => e.stopPropagation());

                var draggable = new ebg.draggable();
                draggable.create(this, card_div, card_div);
                dojo.connect(draggable, 'onEndDragging', this, 'onOrbCardEndDragging');
                dojo.connect(draggable, 'onDragging', this, 'onOrbCardDragging');
                //dojo.connect(draggable, 'onStartDragging', this, 'onOrbCardStartDragging');
                dojo.connect(draggable, 'onStartDragging', this, (item_id, left, top, bDragged)=>{
                            draggable.zoomFactorOriginalElement=1;// fix bug on studio
                            this.onOrbCardStartDragging(item_id, left, top, bDragged);});
            },

            setupNewArtCard: function(card_div, card_type_id, div_id) {
                console.log("setupNewCard");

                var art_type = this.gamedatas.artefact_types[card_type_id];

                var html = '';
                html += '<p>+' + art_type.vp + ' ' + _('Victory points') + '</p>';

                if (typeof art_type.station != 'undefined') {
                    html += '<p>' + _("+1 VP / visible feeding station on Orb") + '</p>';
                }

                html += '<p>' + _("When used") + " : " + _(art_type.descr) + "</p>";

                this.addTooltip(div_id, html, '');
            },

            onOrbCardStartDragging: function(item_id) {
                if (!dojo.hasClass(item_id, 'onOrb')) {
                    this.onOrbCancel();
                }
                dojo.style('orb_target', 'display', 'block');
                dojo.style('orb_target', 'left', '-4000px');
                dojo.style('orb_target', 'top', '-4000px');

                if ((toint(this.getAbsRotationAngle(item_id)) % 180) == 0) {
                    dojo.style('orb_target', 'width', dojo.style(item_id, 'width') + 'px');
                    dojo.style('orb_target', 'height', dojo.style(item_id, 'height') + 'px');
                } else {
                    dojo.style('orb_target', 'height', dojo.style(item_id, 'width') + 'px');
                    dojo.style('orb_target', 'width', dojo.style(item_id, 'height') + 'px');
                }
            },
            onOrbCardEndDragging: function(item_id, left, top, bDragged) {
                dojo.style('orb_target', 'display', 'none');

                if (this.checkAction('orbPlay')) {
                    if (bDragged) {
                        var new_card = dojo.position(item_id);

                        var alien_orb = dojo.position('map_scrollable_oversurface');

                        var x = Math.round((new_card.x - alien_orb.x) / 42);
                        var y = Math.round((new_card.y - alien_orb.y) / 42);

                        var ori = (toint(this.getAbsRotationAngle(item_id.id)) % 360) / 90;

                        // player_hand_orb_item_<id>
                        var card_id = item_id.id.substr(21);

                        // Place our Orb card there
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playOrbCard.html", {
                            lock: true,
                            card: card_id,
                            x: x,
                            y: y,
                            ori: ori,
                            confirmed: false
                        }, this, function() {}, function(is_error) {
                            if (is_error) {
                                // Cancel everything and restore everything in place
                                this.playerHandOrb.updateDisplay();
                            }
                        });
                    }
                } else {
                    this.playerHandOrb.updateDisplay();
                }
            },

            onOrbCancel: function() {
                if (dojo.query('.onOrb').length == 0) {
                    return;
                }
                var selected_card = dojo.query('.onOrb')[0];
                dojo.removeClass(selected_card.id, 'onOrb');
                this.attachToNewParentNoDestroy(selected_card.id, 'player_hand_orb');
                this.rotateTo(selected_card.id, 0);
                this.playerHandOrb.updateDisplay();
                this.adaptOrbZone();
                dojo.style('action_orbcancel', 'display', 'none');
                dojo.style('action_orbconfirm', 'display', 'none');
                 if (this.checkAction('orbSkip', false)) {
                    dojo.style('action_orbskip', 'display', 'inline');
                }
            },
            onOrbConfirm: function() {
                if (!this.checkAction('orbPlay')) {
                    this.playerHandOrb.updateDisplay();
                    return;
                }
                var card = dojo.query('.onOrb')[0];
                var card_pos = dojo.position(card.id);
                var alien_orb_pos = dojo.position('map_scrollable_oversurface');
                var x = Math.round((card_pos.x - alien_orb_pos.x) / 42);
                var y = Math.round((card_pos.y - alien_orb_pos.y) / 42);
                var ori = (toint(this.getAbsRotationAngle(card.id)) % 360) / 90;

                // player_hand_orb_item_<id>
                var card_id = card.id.substr(21);

                // Confirm placement of our Orb card there
                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playOrbCard.html", {
                    lock: true,
                    card: card_id,
                    x: x,
                    y: y,
                    ori: ori,
                    confirmed: true
                }, this, function() {}, function(is_error) {
                    if (is_error) {
                        // Cancel everything and restore everything in place
                        this.playerHandOrb.updateDisplay();
                    }
                });
            },
            onOrbCardDragging: function(item_id) {
                var new_card = dojo.position(item_id);

                var alien_orb = dojo.position('map_scrollable_oversurface');


                //dojo.style('orb_target', 'left', (42 * x) + 'px');
                //dojo.style('orb_target', 'top', (42 * y) + 'px');
            },

            onRotOrbCard: function(evt) {
                // rotcommand_player_hand_orb_item_<id>
                var card_id = evt.currentTarget.id.substr(32);

                dojo.stopEvent(evt);

                var node_id = 'player_hand_orb_item_' + card_id;

                this.rotateTo(node_id, this.getAbsRotationAngle(node_id) + 90);
            },

            onPlayerHandArtSelectionChanged: function() {
                var cards = this.playerHandArt.getSelectedItems();

                // Make sure to use the artefacts used for payment if any
                this.checkPaymentArm();

                if (cards.length == 1) {
                    var card = cards[0];

                    if (card.type == 8 || card.type == 6 || card.type == 11 || card.type == 12) // Military boost or cross orb barrier
                    {
                        if (this.checkAction('useCrossBarier', false)) {
                            this.confirmationDialog(_("Do you want to consume this artifact to cross one Orb barrier?"), dojo.hitch(this, function() {

                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/useArtefact.html", {
                                    lock: true,
                                    artifact: card.id,
                                    reason: 'crossbarrier'
                                }, this, function() {}, function() {});
                            }));

                        } else if (this.checkAction('militaryboost', false)) {
                            this.confirmationDialog(_("Do you want to consume this artifact to temporarly increase your military?"), dojo.hitch(this, function() {

                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/useArtefact.html", {
                                    lock: true,
                                    artifact: card.id,
                                    reason: 'militaryboost'
                                }, this, function() {}, function() {});
                            }));
                        } else if (this.checkAction('orbMoveSelect', false)) {
                            this.showMessage(_("You must select one of your exploration team"), 'info');
                        }
                    } else if (card.type == 3) //  cross walls
                    {
                        if (this.checkAction('useCrossBarier', false)) {
                            this.confirmationDialog(_("Do you want to consume this artifact to cross one Orb wall or barrier?"), dojo.hitch(this, function() {

                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/useArtefact.html", {
                                    lock: true,
                                    artifact: card.id,
                                    reason: 'crosswall'
                                }, this, function() {}, function() {});
                            }));

                        } else if (this.checkAction('orbMoveSelect', false)) {
                            this.showMessage(_("You must select one of your exploration team"), 'info');
                        }
                    } else if (card.type == 5) //  move bonus
                    {
                        if (this.checkAction('useCrossBarier', false)) {
                            this.confirmationDialog(_("Do you want to consume this artifact to move this team 4 more squares?"), dojo.hitch(this, function() {

                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/useArtefact.html", {
                                    lock: true,
                                    artifact: card.id,
                                    reason: 'movebonus'
                                }, this, function() {}, function() {});
                            }));

                        } else if (this.checkAction('orbMoveSelect', false)) {
                            this.showMessage(_("You must select one of your exploration team"), 'info');
                        }
                    } else if (card.type == 4) //  military boost gene
                    {
                        if (this.checkAction('militaryboost', false)) {
                            this.confirmationDialog(_("Do you want to consume this artifact to temporarly increase your military (say no if you want to use it for non-military world payment)?"), dojo.hitch(this, function() {

                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/useArtefact.html", {
                                    lock: true,
                                    artifact: card.id,
                                    reason: 'militaryboost'
                                }, this, function() {
                                    this.showMessage(_("You now have +2 for Gene worlds military until the end of the phase."), 'info');
                                }, function() {});
                            }));

                        }
                    } else if (card.type == 2 || card.type == 9) //  alien ress
                    {
                        if (this.checkAction('sell', false)) {
                            this.confirmationDialog(_("Do you want to consume this artifact to sell an Alien resource?"), dojo.hitch(this, function() {

                                this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/useArtefact.html", {
                                    lock: true,
                                    artifact: card.id,
                                    reason: 'sell'
                                }, this, function() {}, function() {});
                            }));

                        } else if (this.checkAction('consume', false)) {
                            this.checkConsumptionAction();
                        }
                    }


                }

                //
            },

            onPlayerHandOrbSelectionChanged: function() {
                console.log('onPlayerHandSelectionChanged');

                if (this.checkAction('orbPlay', true)) {

                    var cards = this.playerHandOrb.getSelectedItems();
                    var card_id = null;
                    if (cards.length > 0) {
                        card_id = cards[0].id;
                    }

                    // Reset previous selected orb card if any
                    if (this.current_orb_card_id !== null) {
                        var current_card_id = `player_hand_orb_item_${this.current_orb_card_id}`;
                        if ($(current_card_id)) {
                            // Don't unrotate if unselecting the card on the orb
                            if (!dojo.hasClass(current_card_id, 'onOrb')) {
                                this.rotateTo('player_hand_orb_item_' + this.current_orb_card_id, 0);
                            }
                        }
                    }

                    this.current_orb_card_id = null;

                    if (card_id != null) {
                        // Some orb is selected
                        this.current_orb_card_id = card_id;
                        dojo.style('player_hand_orb_item_' + card_id, 'zIndex', 20);

                        // If we have a card on the orb and we're selecting another one, return it
                        if (dojo.query('.onOrb').length > 0) {
                            var orb_card = dojo.query('.onOrb')[0];
                            if (orb_card.id.substr(21) != card_id) {
                                this.onOrbCancel();
                            }
                        }
                    }
                } else {
                    this.playerHandOrb.unselectAll();
                }
            },

            addArtefactsToOrb: function(artefacts) {
                for (var i in artefacts) {
                    var artefact = artefacts[i];
                    dojo.place(this.format_block('jstpl_artefact', artefact), 'orbitems');
                    this.placeOnObject('artefact_' + artefact.id, 'orb_' + artefact.x + '_' + artefact.y);
                    if (artefact['content'] == '!') {
                        dojo.addClass($(`orb_${artefact.x}_${artefact.y}`), 'breeding_tube');
                    }
                }
            },

            // Add an orb card to alien orb (just for the visualization, no interaction)
            addCardToOrb: function(card, from) {
                var backx = Math.floor((toint(card.card_type) - 1) / 10) * 100;
                var backy = Math.floor((toint(card.card_type) - 1) % 10) * 140;

                dojo.place(this.format_block('jstpl_orbcard', {
                    id: card.card_id,
                    backx: backx,
                    backy: backy
                }), 'map_scrollable');

                var x = card.card_x * 42;
                var y = card.card_y * 42;

                if (card.card_ori == 1 || card.card_ori == 3) {
                    // Horizontal card. In such a case, we must adjust x/y
                    x += 20;
                    y -= 20;
                }

                if (typeof from != 'undefined') {
                    this.placeOnObject('orbcard_' + card.card_id, from);
                }

                this.slideToObjectPos('orbcard_' + card.card_id, 'map_scrollable', x, y).play();

                if (typeof from != 'undefined' && from.indexOf('item') != -1) {
                    this.rotateInstantTo('orbcard_' + card.card_id, toint(card.card_ori) * 90);
                } else {
                    this.rotateTo('orbcard_' + card.card_id, toint(card.card_ori) * 90);
                }
            },

            onOrbDraw: function() {
                // Choose to draw
                if (this.checkAction('orbDraw')) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/orbdraw.html", {
                        lock: true
                    }, this, function() {}, function() {});
                }
            },
            onOrbSkip: function() {
                // Choose to skip
                if (this.checkAction('orbSkip')) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/orbskip.html", {
                        lock: true
                    }, this, function() {}, function() {});
                }
            },

            onOrbPass: function() {
                // Choose to pass
                if (this.checkAction('orbPass')) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/orbpass.html", {
                        lock: true
                    }, this, function() {}, function() {});
                }
            },
            onOrbEndMoveAction: function() {
                // Choose to end the move action
                if (this.checkAction('orbEndMoveAction')) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/orbendmoveaction.html", {
                        lock: true
                    }, this, function() {}, function() {});
                }
            },
            onOrbStop: function() {
                // Choose to stop moving this team
                if (this.checkAction('orbStop')) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/orbstop.html", {
                        lock: true
                    }, this, function() {}, function() {});
                }
            },

            // Add an orb place to alien orb (juste for interaction, no visualization)
            extendOrb: function(orb) {
                for (var x in orb) {
                    for (var y in orb[x]) {
                        var place = orb[x][y];
                        place.x = x;
                        place.y = y;
                        this.addOrbSquare(place);
                    }
                }

                this.adaptOrbZone();
            },

            addOrbSquare: function(place) {
                var x = place.x;
                var y = place.y;

                if (!$('orb_' + x + '_' + y)) {
                    // do not exists => must insert

                    var orb_card_border_offset_left = 8;
                    var orb_card_border_offset_top = 7;

                    dojo.place(this.format_block('jstpl_orb', {
                        x: x,
                        y: y,
                        left: x * 42 + orb_card_border_offset_left,
                        top: y * 42 + orb_card_border_offset_top
                    }), 'orbsquares');
                }

                // For debug
                /*       if(place.content != '0')
                           $('orb_'+x+'_'+y).innerHTML = place.content;

                       if(place.n == 'X')
                           dojo.style('orb_'+x+'_'+y , 'borderTop', '1px white dashed');
                       if(place.w == 'X')
                           dojo.style('orb_'+x+'_'+y , 'borderLeft', '1px white dashed');
                       if(place.s == 'X')
                           dojo.style('orb_'+x+'_'+y , 'borderBottom', '1px white dashed');
                       if(place.e == 'X')
                           dojo.style('orb_'+x+'_'+y , 'borderRight', '1px white dashed');
                   */
                dojo.connect($('orb_' + x + '_' + y), 'onclick', this, 'onOrbClick');
            },

            onOrbClick: function(evt) {
                var pieces = evt.currentTarget.id.split('_');

                var x = pieces[1];
                var y = pieces[2];

                if (this.checkAction('orbMoveSelect', false)) {
                    // Is there a team there?
                    var team_there = null;
                    for (var team_id in this.teamPlace) {
                        if (this.teamPlace[team_id] == x + '_' + y) {
                            team_there = team_id;
                        }
                    }

                    if (team_there !== null) {
                        // Select this team

                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/moveTeamSelect.html", {
                            lock: true,
                            id: team_there
                        }, this, function() {}, function() {});
                    } else {
                        this.showMessage(_("You must select one of your exploration team"), 'info');
                    }
                } else if (this.checkAction('orbMoveDest', false)) {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/moveTeam.html", {
                        lock: true,
                        x: x,
                        y: y
                    }, this, function() {}, function() {});
                } else if (this.checkAction('orbBackToSas', false)) {
                    // Is there a team there?
                    var team_there = null;
                    for (var team_id in this.teamPlace) {
                        if (this.teamPlace[team_id] == x + '_' + y) {
                            team_there = team_id;
                        }
                    }

                    if (team_there !== null) {
                        // Select this team

                        dojo.query('.teamSelected').removeClass('teamSelected');
                        dojo.addClass('team_' + team_there, 'teamSelected');
                    } else {
                        var selected = dojo.query('.teamSelected');
                        if (selected.length == 0) {
                            this.showMessage(_("You must select one of your exploration team"), 'info');
                        } else {
                            var team_id = selected[0].id.substr(5);
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/orbBackToSas.html", {
                                lock: true,
                                team: team_id,
                                x: x,
                                y: y
                            }, this, function() {}, function() {
                                dojo.query('.teamSelected').removeClass('teamSelected');
                            });
                        }
                    }
                } else {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/placeTeam.html", {
                        lock: true,
                        x: x,
                        y: y
                    }, this, function() {}, function() {});
                }
            },

            // Adapt Orb zone to its content
            adaptOrbZone: function() {
                var min_x = 0;
                var min_y = 0;
                var max_x = 0;
                var max_y = 0;
                var margin = 80;

                var tile_width = 42;
                var tile_height = 42;

                var whole_zone = dojo.position('orb_wrap');
                var whole_zone_width = whole_zone.w;
                var max_map_container_width = whole_zone_width - 20;

                dojo.query('#alien_orb .orb').forEach(function(node) {
                    var pos_x = dojo.style(node, 'left');
                    var pos_y = dojo.style(node, 'top');

                    min_x = Math.min(min_x, pos_x);
                    max_x = Math.max(max_x, pos_x + tile_width);
                    min_y = Math.min(min_y, pos_y);
                    max_y = Math.max(max_y, pos_y + tile_height);
                });
                if (dojo.query('.onOrb').length > 0) {
                    var orb_card = dojo.query('.onOrb')[0];
                    pos_x = dojo.style(orb_card, 'left');
                    pos_y = dojo.style(orb_card, 'top');
                    tile_width = dojo.style(orb_card, 'width');
                    tile_height = dojo.style(orb_card, 'height');
                    min_x = Math.min(min_x, pos_x);
                    max_x = Math.max(max_x, pos_x + tile_width);
                    min_y = Math.min(min_y, pos_y);
                    max_y = Math.max(max_y, pos_y + tile_height);
                }


                min_x -= margin;
                min_y -= margin;
                max_x += margin;
                max_y += margin;


                var zone_w = max_x - min_x;
                var zone_h = max_y - min_y;
                var center_x = (min_x + max_x) / 2;
                var center_y = (min_y + max_y) / 2;

                var real_zone_width = Math.min(max_map_container_width, zone_w);

                // Then, redim each zone so it can take less space on the screen.
                dojo.style('alien_orb', 'width', real_zone_width + 'px');
                dojo.style('alien_orb', 'height', zone_h + 'px');

                // Then, center the map to the center of the elements
                this.orb.scrollto(-center_x, -center_y);

                // If the width of the map fits in the screen, disable map scrolling, otherwise enable it
                if (zone_w > max_map_container_width) {
                    // Enable scrolling
                    this.orb.enableScrolling();
                } else {
                    // Disable
                    this.orb.disableScrolling();
                }
            },

            setRemainingOrb: function(deck) {
                var a_count = typeof deck[0] != 'undefined' ? deck[0] : 0;
                var b_count = typeof deck[1] != 'undefined' ? deck[1] : 0;
                $('orb_deck_a').innerHTML = a_count;
                $('orb_deck_b').innerHTML = b_count;
                if (b_count == 0) {
                    this.showMessage(_("There is no remaining Orb cards : this is the last round!"), 'info');
                }
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            setupNotifications: function() {
                console.log('notifications subscriptions setup');

                dojo.subscribe('phase_choices', this, "notif_phase_choices");
                dojo.subscribe('prestige_search', this, "notif_prestige_search");
                dojo.subscribe('discard', this, "notif_discard");
                dojo.subscribe('explored_choice', this, "notif_explored_choice");
                dojo.subscribe('keepcards', this, "notif_keepcards");
                dojo.subscribe('cardcost', this, "notif_cardcost");
                dojo.subscribe('playcard', this, "notif_playcard");
                dojo.subscribe('drawCards', this, "notif_drawCards");
                dojo.subscribe('waitdraw', this, 'notif_waitdraw');
                this.notifqueue.setSynchronous('waitdraw', 1000);

                dojo.subscribe('goodproduction', this, "notif_goodproduction");
                dojo.subscribe('updateWindfallPowers', this, "notif_updateWindfallPowers");
                dojo.subscribe('updateProduceTitle', this, "notif_updateProduceTitle");

                dojo.subscribe('updateCardCount', this, "notif_updateCardCount");
                dojo.subscribe('updateScore', this, "notif_updateScore");
                dojo.subscribe('consume', this, "notif_consume");
                dojo.subscribe('tranship', this, "notif_tranship");


                dojo.subscribe('clearTmpMilforce', this, "notif_clearTmpMilforce");
                dojo.subscribe('updateMilforce', this, "notif_updateMilforce");
                dojo.subscribe('updateSpecializedMilitary', this, "notif_updateSpecializedMilitary");
                dojo.subscribe('discardfromtableau', this, "notif_discardfromtableau");

                dojo.subscribe('gambling', this, "notif_gambling");
                dojo.subscribe('rviGambling', this, "notif_rviGambling");
                dojo.subscribe('rviGamblingDone', this, "notif_rviGamblingDone");
                dojo.subscribe('fullfillGoal', this, "notif_fullfillGoal");
                dojo.subscribe('goalProgress', this, "notif_goalProgress");
                dojo.subscribe('oortKindChanged', this, "notif_oortKindChanged");

                dojo.subscribe('drafted', this, "notif_drafted");
                dojo.subscribe('newCardChoice', this, "notif_newCardChoice");

                dojo.subscribe('showTableau', this, "notif_showTableau");
                dojo.subscribe('takeover', this, 'notif_takeover');
                dojo.subscribe('confirmTakeover', this, 'notif_confirmTakeover');
                dojo.subscribe('updateTmpMilforce', this, 'notif_updateTmpMilforce');
                dojo.subscribe('mercenary_used', this, 'notif_mercenary_used');

                dojo.subscribe('updatePrestige', this, "notif_updatePrestige");

                dojo.subscribe('scavengerUpdate', this, 'notif_scavengerUpdate');
                dojo.subscribe('scavengeFromExplore', this, 'notif_scavengeFromExplore');
                dojo.subscribe('clearExplore', this, 'notif_clearExplore');
                this.notifqueue.setSynchronous('scavengeFromExplore', 500);

                dojo.subscribe('pickOrbCards', this, 'notif_pickOrbCards');
                dojo.subscribe('updateOrbCardCount', this, 'notif_updateOrbCardCount');
                dojo.subscribe('changeOrbPriority', this, 'notif_changeOrbPriority');
                dojo.subscribe('putCardOnOrb', this, 'notif_putCardOnOrb');
                dojo.subscribe('updateOrb', this, 'notif_updateOrb');
                dojo.subscribe('orbteam', this, 'notif_orbteam');
                dojo.subscribe('moveTeam', this, 'notif_moveTeam');
                this.notifqueue.setSynchronous('moveTeam', 500);

                dojo.subscribe('destroyArtefact', this, 'notif_destroyArtefact');
                dojo.subscribe('pickArtefact', this, 'notif_pickArtefact');
                dojo.subscribe('drawOrb', this, 'notif_drawOrb');

                dojo.subscribe('consumeArtifact', this, 'notif_consumeArtifact');

                dojo.subscribe('updateWave', this, 'notif_updateWave');
                dojo.subscribe('dealInvasionCards', this, 'notif_dealInvasionCards');
                this.notifqueue.setSynchronous('dealInvasionCards', 2000);
                dojo.subscribe('updateXenoRepulsion', this, 'notif_updateXenoRepulsion');
                dojo.subscribe('updateXenoTieBreaker', this, 'notif_updateXenoTieBreaker');
                dojo.subscribe('empireDefeat', this, 'notif_empireDefeat');
                dojo.subscribe('forceAgainstXeno', this, 'notif_forceAgainstXeno');
                dojo.subscribe('damageWorld', this, 'notif_damageWorld');
                dojo.subscribe('repairWorld', this, 'notif_repairWorld');
                dojo.subscribe('placeAdmiralDisks', this, 'notif_placeAdmiralDisks');
                dojo.subscribe('moveAdmiralDisks', this, 'notif_moveAdmiralDisks');
                this.notifqueue.setSynchronous('moveAdmiralDisks', 1000);
                dojo.subscribe('moveMilitaryVsXenoArrow', this, 'notif_moveMilitaryVsXenoArrow');
                this.notifqueue.setSynchronous('moveMilitaryVsXenoArrow', 1000);

                dojo.subscribe('showMessage', this, 'notif_showMessage');

            },

            notif_placeAdmiralDisks: function(notif) {
                this.placeAdmiralDisks(notif.args);
            },

            // Scroll to have the Xeno track to the bottom of the screen
            scrollToXenoTrack: function() {
                var scrollTo = $('xeno_repulse_track').getBoundingClientRect().bottom;
                scrollTo += window.scrollY;
                scrollTo -= window.innerHeight;
                window.scroll(0, scrollTo);
            },

            notif_moveAdmiralDisks: function(notif) {
                console.log('notif_moveAdmiralDisks');
                console.log(notif);

                if (notif.args.scroll) {
                    this.scrollToXenoTrack();
                }

                for (var i in notif.args.moves) {
                    var move = notif.args.moves[i];
                    var coord = this.getXenoTrackCoord(move.dest, move.height, true);
                    var disk_id = 'admiral_' + move.player_id;
                    var currentHeight = dojo.getStyle(disk_id, 'zIndex');
                    if (currentHeight == "auto") {
                        currentHeight = 1;
                    } else {
                        currentHeight = parseInt(currentHeight);
                    }
                    var maxHeight = Math.max(currentHeight, move.height);
                    dojo.style(disk_id, 'zIndex', maxHeight);
                    this.slideToObjectPos(disk_id, 'xeno_repulse_track', coord['x'], coord['y'], 1000).play();
                    if (move.height != maxHeight) {
                        dojo.style(disk_id, 'zIndex', move.height);
                    }
                }
            },

            notif_moveMilitaryVsXenoArrow: function(notif) {
                console.log('notif_moveMilitaryVsXenoArrow');
                console.log(notif);

                this.scrollToXenoTrack();
                var coord = this.getXenoTrackCoord(notif.args.military_vs_xeno, 0, true);
                coord['y'] += 30;
                if (notif.args.military_vs_xeno <= 12) {
                    coord['x'] += 3;
                } else if (notif.args.military_vs_xeno <= 20) {
                    coord['x'] += 6;
                } else {
                    coord['x'] += 2;
                }
                this.slideToObjectPos('military_vs_xeno_arrow', 'xeno_repulse_track', coord['x'], coord['y'], 1000).play();
            },

            notif_damageWorld: function(notif) {
                this.addCardToTableau(notif.args.card, null, true);
            },

            notif_repairWorld: function(notif) {
                this.addCardToTableau(notif.args.card, null, true);
                if (typeof notif.args.repair_power != 'undefined') {
                    dojo.destroy('windfallpower_repair');
                }

            },

            notif_forceAgainstXeno: function(notif) {
                if (notif.args.force[this.player_id]) {
                    this.gamedatas.gamestate.args.force[this.player_id] = notif.args.force[this.player_id];

                    if ($('titlearg2')) {
                        $('titlearg2').innerHTML = this.gamedatas.gamestate.args.force[this.player_id];
                    }
                }
            },

            notif_empireDefeat: function(notif) {
                this.showMessage(_("Empire has been defeated by Xenos!"), 'info');
                $('empire_defeat').innerHTML = notif.args.defeat;
            },
            notif_updateWave: function(notif) {
                this.setCurrentWave(notif.args.wave, notif.args.remaining);
            },
            notif_dealInvasionCards: function(notif) {
                this.setCurrentWave(notif.args.wave, notif.args.remaining);

                for (var player_id in notif.args.cards) {
                    this.updateInvasion(player_id, notif.args.cards[player_id]);
                }
            },

            updateInvasion: function(player_id, value) {
                if (value == null) {
                    $('invasionforce_' + player_id).innerHTML = '';
                } else if (toint(value) < 100) {
                    $('invasionforce_' + player_id).innerHTML = _("INVASION") + ': ' + value;
                } else {
                    var invasion = toint($('milforce_' + player_id).innerHTML) + toint(value) - 100;
                    $('invasionforce_' + player_id).innerHTML = _("INVASION") + ': ' + invasion;
                }
            },

            notif_updateXenoRepulsion: function(notif) {
                // We have finished animating the board, now it's time to scroll back
                window.scroll(0, this.scroll_pos);
                $('repulse_players').innerHTML = notif.args.force;
            },

            notif_updateXenoTieBreaker: function(notif) {
                for (var i in this.gamedatas.players) {
                    $('xenotiebreaker_' + i).innerHTML = '';
                }
                for (var player_id in notif.args) {
                    $('xenotiebreaker_' + player_id).innerHTML = '(' + notif.args[player_id] + ')';
                }
            },
            notif_showTableau: function(notif) {
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    this.addCardToTableau(card);
                }
            },

            notif_phase_choices: function(notif) {
                console.log('notif_phase_choices');
                console.log(notif);
                this.current_phase_choices = notif.args;
                this.updatePhaseChoices(notif.args);
                if (this.phases_chosen === 0) {
                    dojo.style($('phasechoice_panel'), 'display', 'block');
                }
            },

            notif_prestige_search: function(notif) {
                console.log('notif_prestige_search');
                console.log(notif);
                for (var player_id in notif.args) {
                    if (notif.args[player_id] > 0) {
                        dojo.style('prestige_search_' + player_id, 'visibility', 'visible');
                    } else {
                        dojo.style('prestige_search_' + player_id, 'visibility', 'hidden');
                    }
                }
            },

            notif_discard: function(notif) {
                console.log('notif_discard');
                console.log(notif);

                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    this.playerHand.removeFromStockById(card);
                }
            },

            notif_explored_choice: function(notif) {
                console.log('notif_explored_choice');
                console.log(notif);

                this.exploreSet.removeAll();
                for (var card_id in notif.args) {
                    var card = notif.args[card_id];
                    this.exploreSet.addToStockWithId(card.type, card.id);
                }
            },
            notif_keepcards: function(notif) {
                console.log('notif_keepcards');
                console.log(notif);

                for (var i in notif.args) {
                    var card = notif.args[i];
                    console.log(card);
                    console.log($('explore_set_item_' + card.id));
                    this.playerHand.addToStockWithId(card.type, card.id, $('explore_set_item_' + card.id));
                    this.exploreSet.removeFromStockById(card.id);
                }

                this.exploreSet.removeAll();
            },
            notif_drawCards: function(notif) {
                console.log('notif_drawCards');
                console.log(notif);

                for (var i in notif.args) {
                    var card = notif.args[i];
                    this.playerHand.addToStockWithId(card.type, card.id);
                }

                this.exploreSet.removeAll();
            },
            notif_waitdraw: function() {

            },
            updatePageTitleForCost: function() {
                if (this.paymentMode && this.lastPaymentTitle !== null) {
                    $('pagemaintitletext').innerHTML = this.lastPaymentTitle;
                }
            },

            notif_cardcost: function(notif) {
                console.log('notif_cardcost');
                console.log(notif);

                // Remove any previous card which was not actually played.
                // This is necessary as the onDontPay is not triggered by a notification.
                if (this.nextCardToPlay) {
                    dojo.query('.nextCardToPlay').removeClass('nextCardToPlay');
                    this.playerHand.addToStockWithId(this.nextCardToPlay.type, this.nextCardToPlay.id, $('card_' + this.nextCardToPlay.id));
                    dojo.destroy($('card_wrapper_' + this.nextCardToPlay.id));
                    $('tableau_nbr_' + this.player_id).innerHTML = toint($('tableau_nbr_' + this.player_id).innerHTML) - 1;
                }

                this.paymentCost = notif.args.cost;
                this.nextCardToPlay = notif.args.card;
                this.immediateAlternatives = notif.args.immediate_alternatives;
                this.isMilitarySettle = notif.args.military_force && (this.immediateAlternatives.length == 0)

                if (toint(this.paymentCost) > 0 || this.immediateAlternatives.length > 0 || this.bga.userPreferences.get(10).toString() == '1') {
                    // Go to payment mode
                    this.paymentMode = true;
                    dojo.addClass('hand_panel', 'paymentMode');

                    if (dojo.query('#tableau_panel_' + this.player_id + ' .card_type_181').length > 0) // Galactic Scavengers
                    {
                        dojo.addClass('hand_panel', 'paymentModeScavenger');
                        dojo.query('.scavenger_selected').removeClass('scavenger_selected');
                    }

                    if (notif.args.isWorld) {
                        if (this.isMilitarySettle) {
                            $('pagemaintitletext').innerHTML = _('You may conquer this world by military force');
                        } else {
                            $('pagemaintitletext').innerHTML = dojo.string.substitute(_('You must pay ${cost} cards for this world'), {
                                cost: notif.args.cost
                            });
                        }
                    } else {
                        $('pagemaintitletext').innerHTML = dojo.string.substitute(_('You must pay ${cost} cards for this development'), {
                            cost: notif.args.cost
                        });
                    }
                    this.lastPaymentTitle = $('pagemaintitletext').innerHTML;
                } else {
                    this.lastPaymentTitle = null;
                }

                dojo.empty('generalactions');

                this.onUpdatePageTitle();
                this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

                // Move this card to tableau
                this.addCardToTableau(notif.args.card, $('player_hand_item_' + notif.args.card.id));
                this.playerHand.removeFromStockById(notif.args.card.id);
                // ... display it immediately
                dojo.destroy('player_hand_item_' + notif.args.card.id);
                dojo.addClass($('card_' + notif.args.card.id), 'nextCardToPlay');

                if(this.nextCardToPlay.type == 220 && (this.immediateAlternatives === null || this.immediateAlternatives.length === 0)) {
                    // Alien Oort Cloud Refinery needs to be initialised
                    const call_options = {
                        lock: true,
                        card: this.nextCardToPlay.id,
                        money: '',
                        mode: 'pay',
                    };
                    this.playCardAndPayMaybeOortCloud(this.nextCardToPlay.type, call_options);
                }
            },

            notif_playcard: function(notif) {
                console.log('notif_playcard');
                console.log(notif);

                if (notif.args.player == this.player_id) {
                    dojo.query('.nextCardToPlay').removeClass('nextCardToPlay');

                    if(notif.args.immediate) {
                        // notif_cardcost was not executed in this branch, so we need to add the relevant pieces
                        this.addCardToTableau(notif.args.card, $('player_hand_item_' + notif.args.card.id));
                        this.playerHand.removeFromStockById(notif.args.card.id);
                        dojo.destroy('player_hand_item_' + notif.args.card.id);
                    }

                    if (notif.args.need_scavenging) {
                        this.exploreSet.removeAll();
                        this.explorePanelTitle = dojo.query('#explore_panel > h3')[0].innerHTML;
                        dojo.query('#explore_panel > h3')[0].innerHTML = _("Scavenging");
                        dojo.style('explore_panel', 'display', 'block');
                        dojo.style('action_cancel_payment', 'display', 'none');
                        for (var i in notif.args.money) {
                            var card_id = notif.args.money[i];
                            var card_type = this.playerHand.getItemTypeById(card_id);
                            this.exploreSet.addToStockWithId(card_type, card_id, $('player_hand_item_' + card_id));
                            this.playerHand.removeFromStockById(card_id);
                        }
                        this.exploreSet.updateDisplay();
                        this.lastPaymentTitle = $('pagemaintitletext').innerHTML;
                        $('pagemaintitletext').innerHTML = _("You must choose one card to save under Galactic Scavengers");
                        this.scavenging = true;
                    } else {
                        var scavenger = dojo.query('.stockitem_selected.scavenger_selected');
                        if (scavenger.length == 1) {
                            // player_hand_item_XX
                            scavenger = scavenger[0].id.substr(17);
                        }
                        // Remove discarded cards
                        for (var i in notif.args.money) {
                            var card = notif.args.money[i];
                            if (card == scavenger) {
                                this.slideToObjectAndDestroy('player_hand_item_' + card, dojo.query('.card_type_181')[0]);
                            }
                            this.playerHand.removeFromStockById(card);
                        }
                    }

                    // If this card is NOT in tableau, add it anyway
                    if (dojo.query('#tableau_panel_' + this.player_id + " #card_" + notif.args.card.id).length == 0) {
                        this.addCardToTableau(notif.args.card);
                    }

                    // Reset payment state
                    this.paymentMode = false;
                    this.nextCardToPlay = null;
                    this.paymentCost = 0;
                    this.immediateAlternatives = null;
                    this.isMilitarySettle = false;
                } else {
                    // Another player plays a development card to his tableau
                    this.addCardToTableau(notif.args.card);
                }
            },

            // As the Alien Oort Cloud Refinery is pretty special we need a dedicated function for it
            playCardAndPayMaybeOortCloud: function(card_type, call_options) {
                if (card_type == 220) {
                    this.getGoodChoice(_("Which kind do you want this world to be?"), dojo.hitch(this, function(good) {
                        call_options.oort = good;
                        this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html",
                                      call_options, this, function() {}, function(is_error) {
                                          if (is_error) {
                                              setTimeout(dojo.hitch(this, "onDontPay"), 1000);
                                          }
                                      });
                    }));
                    dojo.style('oort_help', 'display', 'block');
                } else {
                    this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/playCardAndPay.html",
                                  call_options, this, function() {}, function(is_error) {
                                      if (is_error) {
                                          this.onDontPay();
                                      }});
                }
            },
            getGoodChoice: function(text, callback) {
                var oortChoice = new dijit.Dialog({
                    title: text
                });

                dojo.destroy('goodChoiceDlgContent');
                var html = '<div id="goodChoiceDlgContent">';

                html += '<a href="#" class="goodchoice" id="goodchoice_1"><div class="good goodinline good1"></div></a>';
                html += '<a href="#" class="goodchoice" id="goodchoice_2"><div class="good goodinline good2"></div></a>';
                html += '<a href="#" class="goodchoice" id="goodchoice_3"><div class="good goodinline good3"></div></a>';
                html += '<a href="#" class="goodchoice" id="goodchoice_4"><div class="good goodinline good4"></div></a>';

                html += '</div>';

                oortChoice.attr("content", html);
                oortChoice.show();

                dojo.query(".goodchoice").connect('onclick', this, function(evt) {
                    evt.preventDefault();
                    var good_id = evt.currentTarget.id.substr(11);

                    callback(good_id);

                    oortChoice.hide();
                });

            },

            notif_oortKindChanged: function(notif) {
                console.log('notif_oortKindChanged');
                console.log(notif);

                var card_div = $('card_' + notif.args.card.id);
                var card_type_id = notif.args.card.type;

                dojo.replaceClass(card_div, 'kind_' + notif.args.kind_id, 'kind_1 kind_2 kind_3 kind_4');
                this.gamedatas.card_types[card_type_id].kind = notif.args.kind_id;
                $(card_div).setAttribute('oort', this.gamedatas.good_types[notif.args.kind_id]);
            },


            notif_confirmTakeover: function(notif) {
                this.confirmationDialog(
                    dojo.string.substitute(_("Attempt a takover of ${target_name} (defense: ${defense}) with ${takeover_card} (attack: ${attack})?"), {
                        target_name: _(notif.args.target_name),
                        defense: notif.args.defense,
                        takeover_card: _(notif.args.takeovercard_name),
                        attack: notif.args.attack
                    }),
                    dojo.hitch(this,
                        function() {
                            this.ajaxcall("/raceforthegalaxy/raceforthegalaxy/takeover.html", {
                                    lock: true,
                                    card: notif.args.takeovercard_id,
                                    target: notif.args.target_id,
                                    confirmed: true
                                },
                                this,
                                function() {},
                                function(is_error) {
                                    if (is_error) {
                                        this.onDontPay();
                                    }
                                }
                            );
                        }
                    )
                );
            },
            notif_takeover: function(notif) {
                this.moveCardToTableau(notif.args.card, $('card_wrapper_' + notif.args.card_id));

                if (notif.args.good_id != null) {
                    this.addGood({
                        world_id: notif.args.card_id,
                        good_id: notif.args.good_id,
                        good_type: notif.args.good_type
                    });
                }

                // Galactic Scavengers is being taken from this player. Let's hide the scavenger panel
                if (notif.args.card.type == 181 && this.player_id == notif.args.from) {
                    dojo.style($('scavenger_panel'), 'display', 'none');
                }

                $('tableau_nbr_' + notif.args.from).innerHTML = toint($('tableau_nbr_' + notif.args.from).innerHTML) - 1;
                $('tableau_nbr_' + notif.args.to).innerHTML = toint($('tableau_nbr_' + notif.args.to).innerHTML) + 1;

                this.updateVulnerabilities();

            },
            notif_goodproduction: function(notif) {
                console.log('notif_goodproduction');
                console.log(notif);
                this.addGood(notif.args);

                if (typeof notif.args.windfallreason != 'undefined') {
                    dojo.destroy('windfallpower_' + notif.args.windfallreason);
                }
                if (notif.args.produced_by == this.player_id) {
                    ++this.gamedatas.produced_goods[0];
                    ++this.gamedatas.produced_goods[notif.args.good_type];
                }
            },
            notif_updateWindfallPowers: function(notif) {
                console.log('notif_updateWindfallPowers');
                console.log(notif);
                this.updateWindfallPowers(notif.args);
            },
            updateProduceTitle: function(args) {
                var title;
                if (args.action1 == null) {
                    title = _('Production: ${you} may {0}').replace('{0}', _(args.action0));
                } else if (args.action2 == null) {
                    title = _('Production: ${you} may {0} or {1}').replace('{0}', _(args.action0))
                                                                  .replace('{1}', _(args.action1));
                } else {
                    title = _('Production: ${you} may {0}, {1} or {2}').replace('{0}', _(args.action0))
                                                                       .replace('{1}', _(args.action1))
                                                                       .replace('{2}', _(args.action2));
                }
                if (args.world != null) {
                    title = title.replace('{world}', _(args.world));
                }
                this.gamedatas.gamestate.descriptionmyturn = title;
                this.updatePageTitle();
            },
            notif_updateProduceTitle: function(notif) {
                console.log('notif_updateProduceTitle');
                console.log(notif);
                this.updateProduceTitle(notif.args);
            },
            notif_updateCardCount: function(notif) {
                console.log('notif_updateCardCount');
                console.log(notif);

                for (var player_id in this.gamedatas.players) {
                    if (notif.args.hand[player_id]) {
                        $('card_hand_nbr_' + player_id).innerHTML = notif.args.hand[player_id];
                    } else {
                        $('card_hand_nbr_' + player_id).innerHTML = '0';
                    }

                    if (notif.args.tableau[player_id]) {
                        $('tableau_nbr_' + player_id).innerHTML = notif.args.tableau[player_id];
                    } else {
                        $('tableau_nbr_' + player_id).innerHTML = '0';
                    }

                    if (typeof(notif.args.pdeck) != 'undefined' && notif.args.pdeck[player_id]) {
                        $('pdeck_' + player_id).innerHTML = notif.args.pdeck[player_id];
                    }
                }

                if (notif.args.deck) {
                    $('deck_size').innerHTML = notif.args.deck;
                }
            },
            notif_updateScore: function(notif) {
                console.log('notif_updateScore');
                console.log(notif);
                this.bga.playerPanels.getScoreCounter(notif.args.player_id).toValue(toint(notif.args.score));
                if (notif.args.vp) {
                    $('vp_nbr_' + notif.args.player_id).innerHTML = notif.args.vp;
                }
                if (notif.args.vp_delta) {
                    $('vp_nbr_remain').innerHTML = toint($('vp_nbr_remain').innerHTML) - toint(notif.args.vp_delta);
                }

                if (notif.args.war_effort) {
                    $('effortcount_' + notif.args.player_id).innerHTML = notif.args.war_effort;
                }
                if (notif.args.defense_award) {
                    $(`defenseaward_${notif.args.player_id}`).innerHTML = notif.args.defense_award;
                }
                if (notif.args.repulse_goal) {
                    $('repulse_goal').innerHTML = notif.args.repulse_goal;
                    var coord = this.getXenoTrackCoord(notif.args.repulse_goal, 0, true);
                    if (notif.args.repulse_goal <= 12) {
                        coord['x'] += 3;
                        coord['y'] += 35;
                    } else if (notif.args.repulse_goal <= 20) {
                        coord['x'] += 6;
                        coord['y'] += 35;
                    } else {
                        coord['x'] += 2;
                        coord['y'] += 27;
                    }
                    this.slideToObjectPos('repulse_value_arrow', 'xeno_repulse_track', coord['x'], coord['y']).play();

                }
            },
            notif_updatePrestige: function(notif) {
                $('prestige_nbr_' + notif.args.player_id).innerHTML = notif.args.prestige;

                dojo.query('.prestigeleadercount').forEach(dojo.hitch(this, function(node) {
                    node.innerHTML = notif.args.leadertile;
                }));
            },
            notif_consume: function(notif) {
                console.log('consume');
                console.log(notif);

                var good_wrap = $('good_wrap_' + notif.args.good_id);

                if (good_wrap) {
                    if (typeof notif.args.world_id != 'undefined') {
                        // If the consume is followed by a produce, the produce will happen
                        // before the end of the animation and the destruction of the good_wrap
                        // object causing an X2 to appear on it. Change its id to avoid this.
                        good_wrap.setAttribute("id", "good_wrap_to_destroy");

                        // Some good has been consummed => remove it
                        var anim = this.slideToObject(good_wrap, 'card_' + notif.args.world_id);

                        dojo.connect(anim, 'onEnd', this, function() {
                            dojo.destroy(good_wrap);
                        });

                        anim.play();
                    } else {
                        dojo.destroy(good_wrap);
                    }
                }
            },
            notif_tranship: function(notif) {
                console.log('consume');
                console.log(notif);

                for (var source_id in notif.args.goods) {
                    var good_id = notif.args.goods[source_id];

                    dojo.destroy('good_wrap_' + good_id);

                    this.addGood({
                        world_id: notif.args.card_id,
                        good_id: good_id,
                        good_type: 2
                    });

                    this.placeOnObject('good_' + good_id, 'card_' + source_id);
                    this.slideToObject('good_' + good_id, 'good_wrap_' + good_id).play();
                }

            },
            notif_clearTmpMilforce: function(notif) {
                console.log('notif_clearTmpMilforce');
                console.log(notif);
                for (var i in this.gamedatas.players) {
                    $('tmpmilforce_' + i).innerHTML = '';
                }
            },
            notif_updateMilforce: function(notif) {
                console.log('notif_updateMilforce');
                console.log(notif);

                if (notif.args.force != null) {
                    $('milforce_' + notif.args.player_id).innerHTML = notif.args.force;
                }
            },
            notif_updateTmpMilforce: function(notif) {
                $('tmpmilforce_' + notif.args.player).innerHTML = '(+' + notif.args.tmp + ')';
            },
            notif_discardfromtableau: function(notif) {
                console.log('notif_discardfromtableau');
                console.log(notif);

                if (notif.args.player_id) {
                    $('tableau_nbr_' + notif.args.player_id).innerHTML = toint($('tableau_nbr_' + notif.args.player_id).innerHTML) - 1;
                }
                if ($('card_wrapper_' + notif.args.card)) {
                    // If discarding Galactic Scavengers, also hide scavenger panel
                    if (dojo.hasClass('card_wrapper_' + notif.args.card, 'card_type_181')) {
                        dojo.style('scavenger_panel', 'display', 'none');
                    }
                    dojo.destroy('card_wrapper_' + notif.args.card);
                }
                if (notif.args.replace_with) {
                    // Move this card to tableau
                    this.addCardToTableau(notif.args.replace_with, $('player_hand_item_' + notif.args.replace_with.id));
                    this.playerHand.removeFromStockById(notif.args.replace_with.id);
                    // ... display it immediately
                    dojo.destroy('player_hand_item_' + notif.args.replace_with.id);
                }

                this.updateVulnerabilities();
            },
            notif_updateSpecializedMilitary: function(notif) {
                this.updateSpecializedMilitary(notif.args);
            },
            notif_gambling: function(notif) {
                if (notif.args.player_id == this.player_id) {
                    dojo.style('gambling_panel', 'display', 'none');
                }
            },
            notif_rviGambling: function(notif) {
                console.log('notif_rviGambling');
                console.log(notif);

                dojo.style($('explore_panel'), 'display', 'block');
                dojo.style('action_stopConsumption', 'display', 'none');
                this.explorePanelTitle = dojo.query('#explore_panel > h3')[0].innerHTML;
                dojo.query('#explore_panel > h3')[0].innerHTML = _("Gambling Result");

                this.exploreSet.removeAll();
                for (var card_id in notif.args) {
                    var card = notif.args[card_id];
                    this.exploreSet.addToStockWithId(card.type, card.id);
                }
                this.lastPaymentTitle = $('pagemaintitletext').innerHTML;
                $('pagemaintitletext').innerHTML = _("You must choose one card to keep");
            },
            notif_rviGamblingDone: function(notif) {
                console.log('notif_rviGamblingDone');
                console.log(notif);

                dojo.style($('explore_panel'), 'display', 'none');
                dojo.style('action_stopConsumption', 'display', 'inline');
                this.exploreSet.removeAll();
                $('pagemaintitletext').innerHTML = this.lastPaymentTitle;
                dojo.query('#explore_panel > h3')[0].innerHTML = this.explorePanelTitle;
            },
            notif_fullfillGoal: function(notif) {
                if (notif.args.to == 'discard') {
                    // Remove it
                    if (typeof notif.args.from != 'undefined') {
                        this.goals.addToStockWithId(notif.args.type, notif.args.goal, 'goals_' + notif.args.from + '_item_' + notif.args.goal);
                        this.pgoals[notif.args.from].removeFromStockById(notif.args.goal);
                    } else {
                        this.goals.removeFromStockById(notif.args.goal);
                    }

                    if (notif.args.type == 226) {
                        // New prestige leader
                        dojo.query('.prestigeleadercount').forEach(function(node) {
                            node.innerHTML = 0;
                        });
                    }
                } else {
                    // To players
                    for (var player in notif.args.to) {
                        this.pgoals[notif.args.to[player]].addToStockWithId(notif.args.type, notif.args.goal, 'goals_item_' + notif.args.goal);
                    }
                    this.goals.removeFromStockById(notif.args.goal);

                    if (notif.args.type == 226) {
                        // New prestige leader
                        dojo.query('.prestigeleadercount').forEach(function(node) {
                            node.innerHTML = 1;
                        });
                    }
                }
            },
            notif_goalProgress: function(notif) {
                var query = '#goals_';
                if (typeof notif.args.player != 'undefined') {
                    query += notif.args.player + "_";
                }
                query += 'item_' + notif.args.goal;
                var card_div = dojo.query(query)[0];
                if (notif.args.progress != null) {
                    $(card_div.id).setAttribute('progress', notif.args.progress);
                } else {
                    $(card_div.id).removeAttribute('progress');
                }
            },
            notif_drafted: function(notif) {
                this.deck.addToStockWithId(notif.args.card.type, notif.args.card.id, 'player_hand_item_' + notif.args.card.id);
                this.playerHand.removeFromStockById(notif.args.card.id);
            },
            notif_newCardChoice: function(notif) {
                this.playerHand.removeAll();
                for (card_id in notif.args.cards) {
                    card = notif.args.cards[card_id];
                    this.playerHand.addToStockWithId(card.type, card.id);
                }

            },
            notif_mercenary_used: function() {
                dojo.query('.mercenarySelected').removeClass('mercenarySelected');
                dojo.query('.consumeformilitarySelected').removeClass('consumeformilitarySelected');
                dojo.removeClass('hand_panel', 'paymentMode');
                if ($('tableau_panel_' + this.player_id)) {
                    dojo.removeClass('tableau_panel_' + this.player_id, 'consume_for_military_mode');
                }
            },
            notif_scavengerUpdate: function(notif) {
                $('scavengercount').innerHTML = notif.args.count;
                if (notif.args.count == 0) {
                    dojo.style($('scavenger_panel'), 'display', 'none');
                    this.scavengerSet.removeAll();
                }

                // Special case during takeover
                if (typeof notif.args.player_id != 'undefined' && notif.args.player_id == this.player_id) {
                    for (card_id in notif.args.cards) {
                        card = notif.args.cards[card_id];
                        this.scavengerSet.addToStockWithId(card.type, card.id);
                    }
                }

                if (!this.isCurrentPlayerActive()) {
                    return;
                }

                if (typeof notif.args.card != 'undefined') {
                    var card = notif.args.card;
                    if (dojo.style($('scavenger_panel'), 'display') == 'block') {
                        this.scavengerSet.addToStockWithId(card.type, card.id, 'player_hand_item_' + card.id);
                    } else {
                        this.slideToObjectAndDestroy('player_hand_item_' + card.id, dojo.query('.card_type_181')[0]);
                        this.scavengerSet.addToStockWithId(card.type, card.id);
                    }
                }
            },
            notif_scavengeFromExplore: function(notif) {
                var card = notif.args.card;
                if (dojo.style($('scavenger_panel'), 'display') == 'block') {
                    this.scavengerSet.addToStockWithId(card.type, card.id, 'explore_set_item_' + card.id);
                } else {
                    this.slideToObjectAndDestroy('explore_set_item_' + card.id, dojo.query('.card_type_181')[0]);
                    this.scavengerSet.addToStockWithId(card.type, card.id);
                }
                this.scavenging = false;
            },
            notif_clearExplore: function() {
                dojo.style($('explore_panel'), 'display', 'none');
                this.exploreSet.removeAll();
            },

            notif_pickOrbCards: function(notif) {
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];

                    this.playerHandOrb.addToStockWithId(card.type, card.id);
                }
            },
            notif_updateOrbCardCount: function(notif) {
                dojo.query('.orb_card_count').forEach((node) => {node.innerHTML = '3'});
                this.setRemainingOrb(notif.args.deck);
            },
            notif_changeOrbPriority: function(notif) {
                for (var i in notif.args.priority) {
                    this.slideToObject('priority_player_' + i, 'priority_' + notif.args.priority[i]).play();
                }
            },
            // Put a card on the Orb, waiting for the validation of the placement
            notif_putCardOnOrb: function(notif) {
                console.log('notif_putCardOnOrb');
                var x = notif.args.x * 42;
                var y = notif.args.y * 42;

                var card_id = `player_hand_orb_item_${notif.args.card_id}`;
                dojo.addClass(card_id, 'onOrb');

                var card_pos = dojo.position(card_id);
                var map_surface_pos = dojo.position('map_surface');
                var margin = 59;
                if (card_pos.x + card_pos.w < map_surface_pos.x + margin
                    || card_pos.y + card_pos.h < map_surface_pos.y + margin
                    || card_pos.x > map_surface_pos.x + map_surface_pos.w - margin
                    || card_pos.y > map_surface_pos.y + map_surface_pos.h - margin)
                {
                    this.onOrbCancel();
                    this.showMessage(_("The card must be linked to existing Orb."), 'error');
                    return;
                }
                if ($(card_id).parentNode.id != 'map_scrollable') {
                    this.attachToNewParentNoDestroy(card_id, 'map_scrollable');
                    this.playerHandOrb.selectItem(card_id.substr(21));
                    this.current_orb_card_id = card_id.substr(21);
                    dojo.style(card_id, 'zIndex', 20);
                }
                this.slideToObjectPos(card_id, 'map_scrollable', x, y).play();
                this.adaptOrbZone();

                if (this.gamedatas.gamestate.name == 'orbActionPlay') {
                    dojo.style('action_orbskip', 'display', 'none');
                }
                dojo.style('action_orbcancel', 'display', 'inline');

                if (typeof notif.args.error != 'undefined') {
                    this.showMessage(_(notif.args.error), 'error');
                    dojo.style('action_orbconfirm', 'display', 'none');
                } else {
                    dojo.style('action_orbconfirm', 'display', 'inline');
                }
            },
            notif_updateOrb: function(notif) {
                // Add this card to Orb

                if (notif.args.player_id == this.player_id) {
                    var from = 'player_hand_orb_item_' + notif.args.orbcard.id;
                } else {
                    var from = 'overall_player_board_' + notif.args.player_id;
                }

                this.addCardToOrb({
                    card_id: notif.args.orbcard.id,
                    card_type: notif.args.orbcard.type,
                    card_x: notif.args.x,
                    card_y: notif.args.y,
                    card_ori: notif.args.ori
                }, from);


                if (notif.args.player_id == this.player_id) {
                    this.playerHandOrb.removeFromStockById(notif.args.orbcard.id);
                    dojo.destroy("player_hand_orb_item_" + notif.args.orbcard.id);
                }

                if (notif.args.orbcard_type != 'sas2') {
                    var field = $('orb_card_' + notif.args.orbcard_type + '_count_' + notif.args.player_id);
                    var count = parseInt(field.innerHTML) - 1;
                    field.innerHTML = count;
                    if (notif.args.orbcard_type == 'b' && count == 0) {
                        dojo.style(`orb_card_b_wrap_${notif.args.player_id}`, 'display', 'none');
                    }
                }

                for (var i in notif.args.newsquares) {
                    var place = notif.args.newsquares[i];

                    place.n = place.walls.n;
                    place.e = place.walls.e;
                    place.w = place.walls.w;
                    place.s = place.walls.s;

                    this.addOrbSquare(place);
                }

                this.addArtefactsToOrb(notif.args.artefacts);
                this.adaptOrbZone();

            },
            notif_orbteam: function(notif) {
                // Create / move team
                this.setTeam(notif.args);
            },
            notif_moveTeam: function(notif) {
                this.slideToObject('team_' + notif.args.team_id, 'orb_' + notif.args.x + '_' + notif.args.y).play();

                if (notif.args.player_id == this.player_id) {
                    this.teamPlace[notif.args.team_id] = notif.args.x + '_' + notif.args.y;
                    this.gamedatas.orbteamhasmoved = 1;
                }

            },
            setTeam: function(team) {
                if (!$('team_' + team.team_id)) {
                    // Create a new team
                    dojo.place(this.format_block('jstpl_team', team), 'orbitems');

                    if (this.gamedatas.players[team.player].color == 'ff0000') {
                        var backx = 6;
                    } else if (this.gamedatas.players[team.player].color == '008000') {
                        var backx = 1;
                    } else if (this.gamedatas.players[team.player].color == '0000ff') {
                        var backx = 0;
                    } else if (this.gamedatas.players[team.player].color == 'ffa500') {
                        var backx = 7;
                    } else if (this.gamedatas.players[team.player].color == '000000') {
                        var backx = 5;
                    } else if (this.gamedatas.players[team.player].color == 'ffffff') {
                        var backx = 4;
                    }

                    backx *= 30;

                    dojo.style('team_' + team.team_id, 'backgroundPosition', '-' + backx + 'px');

                    this.placeOnObject('team_' + team.team_id, 'orb_' + team.x + '_' + team.y);
                    if (team.player == this.player_id) {
                        this.teamPlace[team.team_id] = team.x + '_' + team.y;
                    }
                }
            },
            notif_destroyArtefact: function(notif) {
                if (notif.args.player_id != this.player_id) {
                    var anim = this.slideToObject('artefact_' + notif.args.artefact_id, 'overall_player_board_' + notif.args.player_id);

                    dojo.connect(anim, 'onEnd', function(node) {
                        dojo.destroy(node);
                    });

                    anim.play();
                }
                var field = $('artefact_' + notif.args.artefact_type + '_count_' + notif.args.player_id);
                field.innerHTML = parseInt(field.innerHTML) + 1;
                if (notif.args.artefact_type == 'B') {
                    dojo.style(`artefact_B_wrap_${notif.args.player_id}`, 'display', 'inline');
                }
            },
            notif_pickArtefact: function(notif) {
                this.playerHandArt.addToStockWithId(notif.args.card.type, notif.args.card.id, 'artefact_' + notif.args.card.id);
                dojo.destroy('artefact_' + notif.args.card.id);
                dojo.removeClass($(`orb_${notif.args.x}_${notif.args.y}`), 'breeding_tube');
            },
            notif_drawOrb: function(notif) {
                var field = $('orb_card_' + notif.args.orbcard_type + '_count_' + notif.args.player_id);
                field.innerHTML = parseInt(field.innerHTML) + 1;
                if (notif.args.orbcard_type == 'b') {
                    dojo.style(`orb_card_b_wrap_${notif.args.player_id}`, 'display', 'inline');
                }
                this.setRemainingOrb(notif.args.deck);
            },
            notif_consumeArtifact: function(notif) {
                if (notif.args.player_id == this.player_id) {
                    this.playerPlayedArt[this.player_id].addToStockWithId(notif.args.artifact_type, notif.args.artifact_id, "player_hand_art_item_" + notif.args.artifact_id);
                    this.playerHandArt.removeFromStockById(notif.args.artifact_id);
                } else {
                    this.playerPlayedArt[notif.args.player_id].addToStockWithId(notif.args.artifact_type, notif.args.artifact_id, "overall_player_board_" + notif.args.player_id);
                }

                if (this.playerPlayedArt[notif.args.player_id].count() > 10) {
                    this.playerPlayedArt[notif.args.player_id].setOverlap(50, 0);
                }

                var art_type = this.gamedatas.artefact_types[notif.args.artifact_type];
                var field = $('artefact_' + art_type.level + '_count_' + notif.args.player_id);
                var count = parseInt(field.innerHTML) - 1;
                field.innerHTML = count;
                if (art_type.level == 'B' && count == 0) {
                    dojo.style(`artefact_B_wrap_${notif.args.player_id}`, 'display', 'none');
                }

            },
            notif_showMessage: function(notif) {
                this.showMessage(_(notif.args.msg), 'info');
            },
            /**
             * This method will attach mobile to a new_parent without destroying, unlike original attachToNewParent which destroys mobile and
             * all its connectors (onClick, etc)
             */
            attachToNewParentNoDestroy: function(mobile, new_parent, relation) {
                //console.log("attaching ",mobile,new_parent,relation);
                if (mobile === null) {
                    console.error("attachToNewParent: mobile obj is null");
                    return;
                }
                if (new_parent === null) {
                    console.error("attachToNewParent: new_parent is null");
                    return;
                }
                if (typeof mobile == "string") {
                    mobile = $(mobile);
                }
                if (typeof new_parent == "string") {
                    new_parent = $(new_parent);
                }
                if (typeof relation == "undefined") {
                    relation = "last";
                }
                var src = dojo.position(mobile);
                dojo.style(mobile, "position", "absolute");
                dojo.place(mobile, new_parent, relation);
                var tgt = dojo.position(mobile);
                var box = dojo.marginBox(mobile);
                var cbox = dojo.contentBox(mobile);
                var left = box.l + src.x - tgt.x;
                var top = box.t + src.y - tgt.y;
                this.positionObjectDirectly(mobile, left, top);
                box.l += box.w - cbox.w;
                box.t += box.h - cbox.h;
                return box;
            },
            positionObjectDirectly: function(mobileObj, x, y) {
                // do not remove this "dead" code some-how it makes difference
                dojo.style(mobileObj, "left"); // bug? re-compute style
                // console.log("place " + x + "," + y);
                dojo.style(mobileObj, {
                    left: x + "px",
                    top: y + "px"
                });
                dojo.style(mobileObj, "left"); // bug? re-compute style
            }
        });
    });
