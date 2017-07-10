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
            'click .user-item': 'moveToUsers'
        },
        initialize: function() {
            this.collection.on('add', this.render.bind(this));
            this.collection.on('remove', this.render.bind(this));
        },
        render: function() {
            var list = this.$el.find('.nav-pills');

            list.html('');
            _.each(this.collection.models, function(user) {
                list.append(this.template(user.attributes));
            }.bind(this));

            return this;
        },
        moveToUsers: function(e) {
            e.preventDefault();
            var li = $(e.target).parent();
            var model = this.collection.findWhere({id: li.data('user-id') });

            dest.remove(model);
            source.add(model);
        }
    });

    var source = new UserCollection();
    var dest = new UserCollection();

    var sourceView;
    var destView;

    function purgeModels() {
        source.reset(null);
        dest.rest(null);

        $('#share-report-source').html('');
        $('#share-report-dest').html('');
    }

    function loadModels() {
        $('#share-report-modal').addClass('modal-loading');
        return $.getJSON('/survey/44/share-reports?recipient_id=1', function (data) {
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
            recipient_id: 1,
            shared: dest.ids()
        };

        return $.post('/survey/44/share-reports', data);
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

        $('#share-report-modal').on('shown.bs.modal', function() {
           loadModels();
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