describe("BacklogService -", function() {
    var $q, $scope, $filter, BacklogService, BacklogItemFactory, ProjectService;

    beforeEach(function() {
        module('backlog', function($provide) {
            $provide.decorator('BacklogItemFactory', function($delegate) {
                spyOn($delegate, "augment");

                return $delegate;
            });

            $provide.decorator('$filter', function() {
                return jasmine.createSpy("$filter").and.callFake(function() {
                    return function() {};
                });
            });

            $provide.decorator('ProjectService', function($delegate) {
                spyOn($delegate, "getProjectBacklog");
                spyOn($delegate, "getProject");

                return $delegate;
            });
        });

        inject(function(
            _$q_,
            _$rootScope_,
            _$filter_,
            _BacklogService_,
            _BacklogItemFactory_,
            _ProjectService_
        ) {
            $q                 = _$q_;
            $scope             = _$rootScope_.$new();
            $filter            = _$filter_;
            BacklogService     = _BacklogService_;
            BacklogItemFactory = _BacklogItemFactory_;
            ProjectService     = _ProjectService_;
        });
    });

    describe("appendBacklogItems() -", function() {
        it("Given an array of items, when I append them to the backlog, then each item will be augmented using BacklogItemFactory and appended to the items' content, and the items object will no longer be marked as loading", function() {
            BacklogService.items.content = [
                { id: 37 }
            ];

            BacklogService.appendBacklogItems([
                { id: 64 },
                { id: 13 }
            ]);

            expect(BacklogService.items.content).toEqual([
                { id: 37 },
                { id: 64 },
                { id: 13 }
            ]);
            expect(BacklogItemFactory.augment).toHaveBeenCalledWith({ id: 64 });
            expect(BacklogItemFactory.augment).toHaveBeenCalledWith({ id: 13 });
            expect(BacklogService.items.loading).toBeFalsy();
        });
    });

    describe("insertItemInUnfilteredBacklog() -", function() {
        it("Given an existing backlog item and an index, when I append it to the unfiltered backlog, then it will be inserted at the given index in the backlog's unfiltered items collection", function() {
            var initial_backlog = [
                { id: 18 },
                { id: 31 }
            ];
            BacklogService.items.content = initial_backlog;

            BacklogService.insertItemInUnfilteredBacklog({ id: 98 }, 1);

            expect(BacklogService.items.content).toEqual([
                { id: 18 },
                { id: 98 },
                { id: 31 }
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });
    });

    describe("removeItemFromUnfilteredBacklog() -", function() {
        it("Given an item in the backlog's unfiltered items collection and given this item's id, when I remove it from the unfiltered backlog, then the item will no longer be in the backlog's unfiltered items collection", function() {
            var initial_backlog = [
                { id: 48 },
                { id: 92 },
                { id: 69 }
            ];
            BacklogService.items.content = initial_backlog;

            BacklogService.removeItemFromUnfilteredBacklog(92);

            expect(BacklogService.items.content).toEqual([
                { id: 48 },
                { id: 69 }
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });

        it("Given an item that was not in the backlog's unfiltered items collection, when I remove it, then the the backlog's unfiltered items collection won't change", function() {
            var initial_backlog = [
                { id: 48 },
                { id: 69 }
            ];
            BacklogService.items.content = initial_backlog;

            BacklogService.removeItemFromUnfilteredBacklog(92);

            expect(BacklogService.items.content).toEqual([
                { id: 48 },
                { id: 69 }
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });
    });

    describe("filterItems() -", function() {
        it("Given filter terms that did not match anything, when I filter backlog items, then the InPropertiesFilter will be called and the items' filtered content collection will be emptied", function() {
            BacklogService.items.content = [
                { id: 37 }
            ];
            var filtered_content_ref = BacklogService.items.filtered_content;

            BacklogService.filterItems('reagreement');

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect(BacklogService.items.filtered_content).toBe(filtered_content_ref);
            expect(BacklogService.items.filtered_content.length).toEqual(0);
        });

        it("Given filter terms that matched items, when I filter backlog items, then the InPropertiesFilter will be called and the items' filtered content collection will be updated", function() {
            BacklogService.items.content = [
                { id: 46 },
                { id: 37 },
                { id: 62 }
            ];
            $filter.and.callFake(function() {
                return function() {
                    return [
                        { id: 46 },
                        { id: 62 }
                    ];
                };
            });

            BacklogService.filterItems('6');

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect(BacklogService.items.filtered_content).toEqual([
                { id: 46 },
                { id: 62 }
            ]);
        });
    });

    describe("loadProjectBacklog() -", function() {
        it("Given a project id, when I load the project backlog, then ProjectService will be called and the backlog object will be updated", function() {
            var project_request         = $q.defer();
            var project_backlog_request = $q.defer();
            ProjectService.getProject.and.returnValue(project_request.promise);
            ProjectService.getProjectBacklog.and.returnValue(project_backlog_request.promise);

            BacklogService.loadProjectBacklog(736);
            project_request.resolve({
                data: {
                    additional_informations: {
                        agiledashboard: {
                            root_planning: {
                                milestone_tracker: {
                                    id: 218,
                                    label: 'Releases'
                                }
                            }
                        }
                    }
                }
            });
            project_backlog_request.resolve({
                allowed_backlog_item_types: {
                    content: [
                        { id: 5, label: 'Epic' }
                    ]
                },
                has_user_priority_change_permission: true
            });
            $scope.$apply();

            expect(ProjectService.getProject).toHaveBeenCalledWith(736);
            expect(ProjectService.getProjectBacklog).toHaveBeenCalledWith(736);
            expect(BacklogService.backlog).toEqual({
                rest_base_route  : 'projects',
                rest_route_id    : 736,
                current_milestone: undefined,
                submilestone_type: {
                    id: 218,
                    label: 'Releases'
                },
                accepted_types: {
                    content: [
                        { id: 5, label: 'Epic' }
                    ]
                },
                user_can_move_cards: true
            });
            expect(BacklogService.backlog.rest_base_route).toEqual('projects');
            expect(BacklogService.backlog.rest_route_id).toEqual(736);
            expect(BacklogService.backlog.current_milestone).toBeUndefined();
            expect(BacklogService.backlog.submilestone_type).toEqual({
                id: 218,
                label: 'Releases'
            });
            expect(BacklogService.backlog.accepted_types.content).toEqual([
                { id: 5, label: 'Epic' }
            ]);
            expect(BacklogService.backlog.user_can_move_cards).toBeTruthy();
        });

    });

    describe("loadMilestoneBacklog() -", function() {
        it("Given a milestone, when I load its backlog, then the backlog object will be updated", function() {
            var milestone = {
                id: 592,
                backlog_accepted_types: {
                    content: [
                        { id: 72, label: 'User Stories' }
                    ]
                },
                sub_milestone_type: { id: 66, label: 'Sprints' },
                has_user_priority_change_permission: true
            };

            BacklogService.loadMilestoneBacklog(milestone);

            expect(BacklogService.backlog.rest_base_route).toEqual('milestones');
            expect(BacklogService.backlog.rest_route_id).toEqual(592);
            expect(BacklogService.backlog.current_milestone).toBe(milestone);
            expect(BacklogService.backlog.submilestone_type).toEqual({
                id: 66,
                label: 'Sprints'
            });
            expect(BacklogService.backlog.accepted_types.content).toEqual([
                { id: 72, label: 'User Stories' }
            ]);
            expect(BacklogService.backlog.user_can_move_cards).toBeTruthy();
        });
    });
});
