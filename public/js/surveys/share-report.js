(function($) {
    var User = Backbone.Model.extend();
    var UserCollection = Backbone.Collection.extend({
        model: User,
        comparator: 'name',
        ids: function() {
            if (this.models.length === 0) return [];

            return this.models.map(function(model) {
                return model.id;
            });
        }
    });

    var SourceItemView = Backbone.View.extend({
        el: '#share-report-source',
        template: _.template('<li data-user-id="<%= id %>"><a href="#" class="user-item"><%= name %></a></li>'),
        events: {
            'click .user-item': 'moveToShared',
            'keyup #share-report-source-search': 'search'
        },
        initialize: function() {
            this.collection.on('add', this.render.bind(this));
            this.collection.on('remove', this.render.bind(this));
            this.collection.on('reset', this.render.bind(this));
        },
        render: function() {
            var list = this.$el.find('.nav-pills');

            list.html('');
            _.each(this.collection.models, function(user) {
                list.append(this.template(user.attributes));
            }.bind(this));

            return this;
        },
        moveToShared: function(e) {
            e.preventDefault();
            var li = $(e.target).parent();
            var model = this.collection.findWhere({id: li.data('user-id') });

            source.remove(model);
            dest.add(model);
        },
        search: function(e) {
            var query = $.trim($(e.target).val());
            var list = this.$el.find('.nav-pills');
            list.children().removeClass('search-result');

            if (query === '') {
                list.removeClass('searching');
            } else {
                var fuse = new Fuse(this.collection.toJSON(), {
                    keys: ['name'],
                    id: 'id',
                    threshold: 0.1
                });
                var results = fuse.search(query);

                list.addClass('searching');
                _.each(results, function(id) {
                    list.find('[data-user-id="'+ id +'"]').addClass('search-result');
                });
            }
        }
    });
    
    var DestinationItemView = Backbone.View.extend({
        el: '#share-report-dest',
        template: _.template('<li data-user-id="<%= id %>"><a href="#" class="user-item"><%= name %><i class="glyphicon glyphicon-remove"></i></a></li>'),
        events: {
            'click .user-item': 'moveToUsers',
            'click #share-report-dest-clear': 'clearAll'
        },
        initialize: function() {
            this.collection.on('add', this.render.bind(this));
            this.collection.on('remove', this.render.bind(this));
            this.collection.on('reset', this.render.bind(this));
        },
        render: function() {
            var list = this.$el.find('.nav-pills');

            list.html('');
            _.each(this.collection.models, function(user) {
                list.append(this.template(user.attributes));
            }.bind(this));

            if (this.collection.length > 0) {
                this.$el.find('#share-report-dest-clear').removeClass('hidden');
            } else {
                this.$el.find('#share-report-dest-clear').addClass('hidden');
            }

            return this;
        },
        moveToUsers: function(e) {
            e.preventDefault();
            var li = $(e.target).parent();
            var model = this.collection.findWhere({id: li.data('user-id') });

            dest.remove(model);
            source.add(model);
        },
        clearAll: function(e) {
            e.preventDefault();
            dest.each(function(model) {
                source.add(model);
            });
            dest.reset(null);
        }
    });

    var source = new UserCollection();
    var dest = new UserCollection();

    var sourceView;
    var destView;

    function purgeModels() {
        source.reset(null);
        dest.reset(null);

        $('#share-report-modal').addClass('modal-loading');
    }

    function loadModels(opts) {
        var url = '/survey/'+ opts.survey +'/share-reports';
        if (typeof opts.recipient !== 'undefined') {
            url += '?recipient_id='+ opts.recipient;
        }

        $('#share-report-modal').addClass('modal-loading');
        return $.getJSON(url, function (data) {
            $.each(data.users, function(i, user) {
                source.add({
                    id: user.id,
                    name: user.name
                });
                source.sort();
            });
            $.each(data.shared, function (i, user) {
                dest.add({
                    id: user.id,
                    name: user.name
                });
            });
            $('#share-report-modal').removeClass('modal-loading');
        });
    }

    function saveModels() {
        var data = {
            shared: dest.ids()
        };
        var modal = $('#share-report-modal');
        var surveyId = modal.data('survey-id');
        var url = '/survey/'+ surveyId +'/share-reports';

        if (typeof modal.data('recipient-id') !== 'undefined') {
            data['recipient_id'] = modal.data('recipient-id');
        }

        return $.post(url, data);
    }

    function setupCollections() {
        dest.on('add', function(user) {
            var view = new DestinationItemView({ model: user });
            $('#share-report-dest').append(view.render().el);
        });
        dest.on('remove', function(user) {
            $('#share-report-dest li[data-user-id="'+ user.id +'"]').remove();
        });
    }

    function setupViews() {
        sourceView = new SourceItemView({
            collection: source
        });
        destView = new DestinationItemView({
            collection: dest
        });
    }

    $(document).on('ready', function() {
        setupViews();

        $('#share-report-modal').on('shown.bs.modal', function(e) {
            var modal = $(e.target);
            var btn = $(e.relatedTarget);
            loadModels({
                survey: btn.data('survey-id'),
                recipient: btn.data('recipient-id')
            });

            modal.data('survey-id', btn.data('survey-id'));
            modal.data('recipient-id', btn.data('recipient-id'));
        });
        $('#share-report-modal').on('hidden.bs.modal', function(e) {
            purgeModels();
        });
        $('#share-report-save').on('click', function(e) {
            e.preventDefault();
            saveModels().then(function() {
                purgeModels();
                $('#share-report-modal').addClass('modal-loading');
            });
            $('#share-report-modal .close').click();
        });
    });

})(jQuery);