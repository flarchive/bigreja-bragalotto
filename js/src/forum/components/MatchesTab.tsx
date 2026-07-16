import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import Placeholder from 'flarum/common/components/Placeholder';
import EventCard from './EventCard';
import PickemEvent from '../../common/models/Event';
import Season from '../../common/models/Season';
import Team from '../../common/models/Team';
import Week from '../../common/models/Week';
import Competition from '../../common/models/Competition';

interface IMatchesTabAttrs {
  picks: Record<string, any>;
  onPickChange: (picks: Record<string, any>) => void;
}

export default class MatchesTab extends Component<IMatchesTabAttrs> {
  private selectedSeason: string = 'all';
  private selectedCompetition: string = 'all';
  private selectedWeek: string = 'all';
  private selectedTeam: string = 'all';
  private selectedStatus: string = 'all';
  private loading: boolean = false;
  private events: PickemEvent[] = [];
  private hasMore: boolean = false;
  private limit: number = 10;
  private pickLoading: Set<number> = new Set();

  oninit(vnode: any) {
    super.oninit(vnode);

    const seasons = app.store.all<Season>('bragalotto-seasons');
    if (seasons.length === 1) {
      this.selectedSeason = seasons[0].id()!;
    }

    this.loadEvents(true);
  }

  buildFilters() {
    const filters: any = {};

    if (this.selectedWeek !== 'all') {
      filters.week = this.selectedWeek;
    } else if (this.selectedCompetition !== 'all') {
      filters.competition = this.selectedCompetition;
    } else if (this.selectedSeason !== 'all') {
      // Filter by all competitions in this season
      const competitionIds = app.store.all<Competition>('bragalotto-competitions')
        .filter(c => String(c.seasonId()) === this.selectedSeason)
        .map(c => String(c.id()));
      if (competitionIds.length > 0) {
        filters.competition = competitionIds.join(',');
      }
    }

    if (this.selectedTeam !== 'all') {
      filters.team = this.selectedTeam;
    }

    if (this.selectedStatus !== 'all') {
      filters.status = this.selectedStatus;
    }

    return filters;
  }

  loadEvents(clear: boolean = false) {
    this.loading = true;
    m.redraw();

    const offset = clear ? 0 : this.events.length;
    const filters = this.buildFilters();

    app.store.find<PickemEvent[]>('bragalotto-events', {
      include: 'homeTeam,awayTeam,week',
      filter: filters,
      sort: '-matchDate',
      page: { limit: this.limit, offset: offset }
    }).then((results) => {
      if (clear) {
        this.events = results;
      } else {
        this.events = [...this.events, ...results];
      }
      
      this.hasMore = results.length >= this.limit;
      this.fetchPicksForEvents(results);

    }).catch(error => {
      console.error(error);
    }).finally(() => {
      this.loading = false;
      m.redraw();
    });
  }
  
  fetchPicksForEvents(events: PickemEvent[]) {
    if (!app.session.user || !app.forum.attribute('bragalotto.makePicks')) return;
    
    const currentPicks = this.attrs.picks || {};
    const eventsToFetch = events.filter(e => !currentPicks[String(e.id())]);
    if (eventsToFetch.length === 0) return;

    const eventIds = eventsToFetch.map(e => e.id()).join(',');

    app.store.find<any[]>('bragalotto-picks', {
      filter: { user: app.session.user.id(), event: eventIds }
    }).then((results) => {
        if (Array.isArray(results) && results.length > 0) {
            const newPicks = results.reduce((acc: any, pick: any) => {
                 const eId = pick.event() ? pick.event().id() : pick.attribute('eventId');
                 if(eId) acc[String(eId)] = pick;
                 return acc;
            }, {});
            const merged = { ...this.attrs.picks, ...newPicks };
            this.attrs.onPickChange(merged);
            m.redraw();
        }
    });
  }

  async makePick(eventId: number, outcome: string) {
    const eventIdStr = String(eventId);
    const existingPick = this.attrs.picks[eventIdStr];

    this.pickLoading.add(eventId);
    m.redraw();

    try {
      const updateParent = (newPickVal: any) => {
        const newPicks = { ...this.attrs.picks };
        if (newPickVal === null) {
            delete newPicks[eventIdStr];
        } else {
            newPicks[eventIdStr] = newPickVal;
        }
        this.attrs.onPickChange(newPicks);
      };

      if (existingPick && existingPick.selectedOutcome() === outcome) {
        await existingPick.delete();
        updateParent(null);
        app.alerts.show({ type: 'success' }, app.translator.trans('bigreja-bragalotto.lib.messages.deleted'));
      } else if (existingPick) {
        const updated = await existingPick.save({ selectedOutcome: outcome });
        updateParent(updated);
        app.alerts.show({ type: 'success' }, app.translator.trans('bigreja-bragalotto.lib.messages.saved'));
      } else {
        const newPick = app.store.createRecord('bragalotto-picks');
        const saved = await newPick.save({ 
          eventId: eventId,
          selectedOutcome: outcome 
        });
        updateParent(saved);
        app.alerts.show({ type: 'success' }, app.translator.trans('bigreja-bragalotto.lib.messages.saved'));
      }
    } catch (error: any) {
      console.error('Pick error:', error);
      if (error.response && error.response.errors && error.response.errors[0]) {
        app.alerts.show({ type: 'error' }, error.response.errors[0].detail);
      } else {
        app.alerts.show({ type: 'error' }, app.translator.trans('bigreja-bragalotto.lib.messages.invalid_outcome'));
      }
    } finally {
      this.pickLoading.delete(eventId);
      m.redraw();
    }
  }

  view() {
    const allSeasons = app.store.all<Season>('bragalotto-seasons');
    const allTeams = app.store.all<Team>('bragalotto-teams');
    const allCompetitions = app.store.all<Competition>('bragalotto-competitions').filter(
      c => this.selectedSeason === 'all' || String(c.seasonId()) === this.selectedSeason
    );
    const hasEvents = this.events.length > 0;

    let availableWeeks: Week[] = [];
    if (this.selectedCompetition !== 'all') {
      availableWeeks = app.store.all<Week>('bragalotto-weeks')
        .filter(w => String(w.competitionId()) === this.selectedCompetition)
        .sort((a, b) => parseInt(String(a.weekNumber() || 0)) - parseInt(String(b.weekNumber() || 0)));
    } else if (this.selectedSeason !== 'all') {
      const compIds = allCompetitions.map(c => String(c.id()));
      availableWeeks = app.store.all<Week>('bragalotto-weeks')
        .filter(w => compIds.includes(String(w.competitionId())))
        .sort((a, b) => parseInt(String(a.weekNumber() || 0)) - parseInt(String(b.weekNumber() || 0)));
    }

    const currentPicks = this.attrs.picks || {};

    return (
      <div>
        <div className="EventsTab-filters PickemPage-filters">
          <div className="FilterGroup">
            <label>
              <i className="fas fa-calendar-alt" />
              <span>{app.translator.trans('bigreja-bragalotto.lib.common.season')}</span>
            </label>
            <select
              className="FormControl"
              value={this.selectedSeason}
              onchange={(e: any) => {
                this.selectedSeason = e.target.value;
                this.selectedCompetition = 'all';
                this.selectedWeek = 'all';
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('bigreja-bragalotto.lib.filters.all')}</option>
              {allSeasons.map((season: Season) => (
                <option value={season.id()}>{season.name()}</option>
              ))}
            </select>
          </div>

          {allCompetitions.length > 0 && (
            <div className="FilterGroup">
              <label>
                <i className="fas fa-trophy" />
                <span>{app.translator.trans('bigreja-bragalotto.lib.nav.competitions')}</span>
              </label>
              <select
                className="FormControl"
                value={this.selectedCompetition}
                onchange={(e: any) => {
                  this.selectedCompetition = e.target.value;
                  this.selectedWeek = 'all';
                  this.loadEvents(true);
                }}
              >
                <option value="all">{app.translator.trans('bigreja-bragalotto.lib.filters.all')}</option>
                {allCompetitions.map((c: Competition) => (
                  <option value={c.id()}>{c.name()}</option>
                ))}
              </select>
            </div>
          )}

          <div className="FilterGroup">
            <label>
              <i className="fas fa-calendar-week" />
              <span>{app.translator.trans('bigreja-bragalotto.lib.common.week')}</span>
            </label>
            <select
              className="FormControl"
              value={this.selectedWeek}
              disabled={this.selectedSeason === 'all' || availableWeeks.length === 0}
              onchange={(e: any) => {
                this.selectedWeek = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('bigreja-bragalotto.lib.filters.all')}</option>
              {availableWeeks.map((week: Week) => (
                <option value={week.id()}>{week.name()}</option>
              ))}
            </select>
          </div>

          <div className="FilterGroup">
            <label>
              <i className="fas fa-users" />
              <span>{app.translator.trans('bigreja-bragalotto.lib.common.team')}</span>
            </label>
            <select
              className="FormControl"
              value={this.selectedTeam}
              onchange={(e: any) => {
                this.selectedTeam = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('bigreja-bragalotto.lib.filters.all')}</option>
              {allTeams.map((team: Team) => (
                <option value={team.id()}>{team.name()}</option>
              ))}
            </select>
          </div>

          <div className="FilterGroup">
            <label>
              <i className="fas fa-filter" />
              <span>{app.translator.trans('bigreja-bragalotto.lib.common.status')}</span>
            </label>
            <select
              className="FormControl"
              value={this.selectedStatus}
              onchange={(e: any) => {
                this.selectedStatus = e.target.value;
                this.loadEvents(true);
              }}
            >
              <option value="all">{app.translator.trans('bigreja-bragalotto.lib.filters.all')}</option>
              <option value="scheduled">{app.translator.trans('bigreja-bragalotto.lib.status.scheduled')}</option>
              <option value="closed">{app.translator.trans('bigreja-bragalotto.lib.status.closed')}</option>
              <option value="finished">{app.translator.trans('bigreja-bragalotto.lib.status.finished')}</option>
            </select>
          </div>
        </div>

        {!hasEvents && !this.loading ? (
          <Placeholder text={app.translator.trans('bigreja-bragalotto.lib.messages.no_matches')} />
        ) : (
          <div className="MatchesList">
            {this.events.map((event: PickemEvent) => {
              const eventIdStr = String(event.id());
              const pick = currentPicks[eventIdStr];
              const isLoading = this.pickLoading.has(Number(event.id()));

              return (
                <EventCard
                  event={event}
                  pick={pick}
                  onMakePick={(eventId, outcome) => this.makePick(eventId, outcome)}
                  isLoading={isLoading}
                />
              );
            })}
          </div>
        )}

        {this.hasMore && (
          <div className="LoadMore">
             <Button
              className="Button Button--primary"
              loading={this.loading}
              onclick={() => this.loadEvents(false)}
            >
              {app.translator.trans('bigreja-bragalotto.lib.buttons.load_more')}
            </Button>
          </div>
        )}
      </div>
    );
  }
}