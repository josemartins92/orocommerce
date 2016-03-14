define(function(require) {
    'use strict';

    var TotalsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2bpricing/js/app/components/totals-component
     * @extends oroui.app.components.base.Component
     * @class orob2bpricing.app.components.TotalsComponent
     */
    TotalsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            url: 'orob2b_pricing_frontend_entity_totals',
            entityClassName: '',
            entityId: 0,
            selectors: {
                form: '',
                template: '.totals-template',
                subtotals: '.totals-container'
            },
            events: [],
            skipMaskView: false
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $method: null,

        /**
         * @property {jQuery}
         */
        $subtotals: null,

        /**
         * @property {Object}
         */
        template: null,

        /**
         * @property {String}
         */
        formData: '',

        /**
         * @property {String}
         */
        eventName: '',

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            if (this.options.url.length === 0) {
                return;
            }

            this.$el = options._sourceElement;
            this.$form = $(this.options.selectors.form);
            this.$subtotals = this.$el.find(this.options.selectors.subtotals);
            this.template = _.template($(this.options.selectors.template).text());
            this.loadingMaskView = new LoadingMaskView({container: this.$el});
            this.eventName = 'total-target:changing';

            this.updateTotals();

            mediator.on('line-items-totals:update', this.updateTotals, this);
            mediator.on('update:account', this.updateTotals, this);
            mediator.on('update:website', this.updateTotals, this);
            mediator.on('update:currency', this.updateTotals, this);

            var self = this;
            _.each(this.options.events, function(event) {
                mediator.on(event, self.updateTotals, self);
            });
        },

        /**
         * Get and render subtotals
         */
        updateTotals: function(e) {
            if (!this.options.skipMaskView) {
                this.loadingMaskView.show();
            }

            if (this.getTotals.timeoutId) {
                clearTimeout(this.getTotals.timeoutId);
            }

            this.getTotals.timeoutId = setTimeout(_.bind(function() {
                this.getTotals.timeoutId = null;

                var promises = [];
                mediator.trigger(this.eventName, promises);

                if (promises.length) {
                    $.when.apply($, promises).done(_.bind(this.updateTotals, this, e));
                } else {
                    this.getTotals(_.bind(function(subtotals) {
                        this.loadingMaskView.hide();
                        if (!subtotals) {
                            return;
                        }
                        this.render(subtotals);
                    }, this));
                }
            }, this), 100);
        },

        /**
         * Get order subtotals
         *
         * @param {Function} callback
         */
        getTotals: function(callback) {
            var self = this;

            var params = {
                entityClassName: this.options.entityClassName,
                entityId: this.options.entityId
            };

            var formData = this.$form.find(':input[data-ftid]').serialize();
            this.formData = formData;

            $.ajax({
                url: routing.generate(this.options.url, params),
                type: 'GET',
                success: function (response) {
                    if (formData === self.formData) {
                        //data doesn't change after ajax call
                        var totals = response || {};
                        callback(totals);
                    }
                    //self.updateTotalsContrainer(response);
                },
                error: function(response) {
                    //callback(response);
                }
            });
        },

        /**
         * Render totals
         *
         * @param {Object} totals
         */
        render: function(totals) {
            _.each(totals.subtotals, function(subtotal) {
                subtotal.formattedAmount = NumberFormatter.formatCurrency(subtotal.amount, subtotal.currency);
            });

            totals.total.formattedAmount = NumberFormatter.formatCurrency(totals.total.amount, totals.total.currency);

            this.$subtotals.html(this.template({
                totals: totals
            }));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('line-items-totals:update', this.updateTotals, this);
            mediator.off('update:account', this.updateTotals, this);
            mediator.off('update:website', this.updateTotals, this);
            mediator.off('update:currency', this.updateTotals, this);
            var self = this;
            _.each(this.options.events, function(event) {
                mediator.off(event, self.updateTotals, self);
            });
            TotalsComponent.__super__.dispose.call(this);
        }
    });

    return TotalsComponent;
});
