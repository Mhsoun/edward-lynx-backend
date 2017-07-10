(function($) {
    var User = Backbone.Model.extend();
    var UserCollection = Backbone.Collection.extend({
        model: User,
        comparator: 'name',
        ids: function() {
            return this.models.map(function(model) {
                return model.id;
            });
        }
    });

    var SourceItemView = Backbone.View.extend({
        tagName: 'li',
        template: _.template('<a href="#"><%= name %></a>'),
        events: {
            'click': 'moveToShared'
        },
        attributes: function() {
            return {
                'data-user-id': this.model.id
            }
        },
        render: function() {
            this.$el.html(this.template(this.model.attributes));
            return this;
        },
        moveToShared: function(e) {
            e.preventDefault();
            source.remove(this.model);
            dest.add(this.model);
        }
    });
    var DestinationItemView = Backbone.View.extend({
        tagName: 'li',
        template: _.template('<a href="#"><%= name %><i class="glyphicon glyphicon-remove"></i></a>'),
        events: {
            'click': 'moveToUsers'
        },
        attributes: function() {
            return {
                'data-user-id': this.model.id
            }
        },
        render: function() {
            this.$el.html(this.template(this.model.attributes));
            return this;
        },
        moveToUsers: function(e) {
            e.preventDefault();
            dest.remove(this.model);
            source.add(this.model);
        }
    });

    var source = new UserCollection();
    var dest = new UserCollection();

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
        source.on('add', function(user) {
            var view = new SourceItemView({ model: user });
            $('#share-report-source').append(view.render().el);
        });
        source.on('remove', function(user) {
            $('#share-report-source li[data-user-id="'+ user.id +'"]').remove();
        });
        dest.on('add', function(user) {
            var view = new DestinationItemView({ model: user });
            $('#share-report-dest').append(view.render().el);
        });
        dest.on('remove', function(user) {
            $('#share-report-dest li[data-user-id="'+ user.id +'"]').remove();
        });
    };

    $(document).on('ready', function() {
        setupCollections();

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