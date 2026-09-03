import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import PickemEvent from '../../../common/models/Event';
import Week from '../../../common/models/Week';
import Team from '../../../common/models/Team';

interface IEventModalAttrs {
  event?: PickemEvent | null;
  onsave: () => void;
}

export default class EventModal extends Modal<IEventModalAttrs> {
  private event: PickemEvent | null | undefined;
  private homeTeamId: string = '0';
  private awayTeamId: string = '0';
  private weekId: string = '0';
  private matchDate: string = '';
  private cutoffDate: string = '';
  private allowDraw: boolean = false;
  private status: string = 'scheduled';
  private loading: boolean = false;

  private homeTeamFilter: string = '';
  private awayTeamFilter: string = '';

  oninit(vnode: any) {
    super.oninit(vnode);

    this.event = this.attrs.event;
    if (this.event) {
      this.homeTeamId = this.event.homeTeamId() || '0';
      this.awayTeamId = this.event.awayTeamId() || '0';
      this.weekId = this.event.weekId() || '0';
      this.matchDate = this.formatDateForInput(this.event.matchDate());
      this.cutoffDate = this.formatDateForInput(this.event.cutoffDate());
      this.allowDraw = this.event.allowDraw() || false;
      this.status = this.event.status() || 'scheduled';
    }
  }

  formatDateForInput(date: Date | undefined): string {
    if (!date) return '';
    
    const YYYY = date.getFullYear();
    const MM = (date.getMonth() + 1).toString().padStart(2, '0');
    const DD = date.getDate().toString().padStart(2, '0');
    const HH = date.getHours().toString().padStart(2, '0');
    const mm = date.getMinutes().toString().padStart(2, '0');

    return `${YYYY}-${MM}-${DD}T${HH}:${mm}`;
  }

  className(): string {
    return 'EventModal Modal--medium';
  }

  title(): string {
    return this.event
      ? app.translator.trans('bigreja-bragalotto.lib.actions.edit')
      : app.translator.trans('bigreja-bragalotto.lib.actions.create');
  }

  content() {
    const teams = app.store.all<Team>('bragalotto-teams');
    const weeks = app.store.all<Week>('bragalotto-weeks');

    const filteredHomeTeams = teams.filter(team => 
      team.name()?.toLowerCase().includes(this.homeTeamFilter.toLowerCase())
    );
    if (this.homeTeamId !== '0' && !filteredHomeTeams.find(t => t.id() === this.homeTeamId)) {
        const selected = teams.find(t => t.id() === this.homeTeamId);
        if (selected) filteredHomeTeams.unshift(selected);
    }
    const homeTeamOptions: Record<string, string> = filteredHomeTeams.reduce((acc, team) => {
      acc[team.id()!] = team.name()!;
      return acc;
    }, {} as Record<string, string>);


    const filteredAwayTeams = teams.filter(team => 
        team.name()?.toLowerCase().includes(this.awayTeamFilter.toLowerCase())
    );
    if (this.awayTeamId !== '0' && !filteredAwayTeams.find(t => t.id() === this.awayTeamId)) {
        const selected = teams.find(t => t.id() === this.awayTeamId);
        if (selected) filteredAwayTeams.unshift(selected);
    }
    const awayTeamOptions: Record<string, string> = filteredAwayTeams.reduce((acc, team) => {
      acc[team.id()!] = team.name()!;
      return acc;
    }, {} as Record<string, string>);


    const weekOptions: Record<string, string> = weeks.reduce((acc, week) => {
      acc[week.id()!] = week.name()!;
      return acc;
    }, {} as Record<string, string>);

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.common.week')}</label>
            <Select
              className="FormControl"
              value={this.weekId}
              onchange={(value: string) => { this.weekId = value; }}
              options={weekOptions}
              default="0"
            >
              <option value="0">{app.translator.trans('bigreja-bragalotto.lib.form.no_week')}</option>
            </Select>
          </div>

          {/* HOME TEAM SELECTION */}
          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.home_team')}</label>
            <input 
                className="FormControl" 
                type="text" 
                placeholder={app.translator.trans('bigreja-bragalotto.lib.form.search_team')}
                value={this.homeTeamFilter}
                oninput={(e: InputEvent) => { this.homeTeamFilter = (e.target as HTMLInputElement).value; }}
                style="margin-bottom: 5px; font-size: 12px;"
            />
            <Select
              className="FormControl"
              value={this.homeTeamId}
              onchange={(value: string) => { this.homeTeamId = value; }}
              options={homeTeamOptions}
              default="0"
            >
              <option value="0">{app.translator.trans('bigreja-bragalotto.lib.form.select_team')}</option>
            </Select>
          </div>

          {/* AWAY TEAM SELECTION */}
          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.away_team')}</label>
            <input 
                className="FormControl" 
                type="text" 
                placeholder={app.translator.trans('bigreja-bragalotto.lib.form.search_team')}
                value={this.awayTeamFilter}
                oninput={(e: InputEvent) => { this.awayTeamFilter = (e.target as HTMLInputElement).value; }}
                style="margin-bottom: 5px; font-size: 12px;"
            />
            <Select
              className="FormControl"
              value={this.awayTeamId}
              onchange={(value: string) => { this.awayTeamId = value; }}
              options={awayTeamOptions}
              default="0"
            >
              <option value="0">{app.translator.trans('bigreja-bragalotto.lib.form.select_team')}</option>
            </Select>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.headers.match_date')}</label>
            <input
              className="FormControl"
              type="datetime-local"
              value={this.matchDate}
              oninput={(e: InputEvent) => { this.matchDate = (e.target as HTMLInputElement).value; }}
              required
            />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.headers.cutoff_date')}</label>
            <input
              className="FormControl"
              type="datetime-local"
              value={this.cutoffDate}
              oninput={(e: InputEvent) => { this.cutoffDate = (e.target as HTMLInputElement).value; }}
              required
            />
          </div>

          <div className="Form-group">
            <label className="checkbox">
              <input
                type="checkbox"
                checked={this.allowDraw}
                onchange={(e: InputEvent) => { this.allowDraw = (e.target as HTMLInputElement).checked; }}
              />
              {app.translator.trans('bigreja-bragalotto.lib.form.allow_draw')}
            </label>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.common.status')}</label>
            <Select
              className="FormControl"
              value={this.status}
              onchange={(value: string) => { this.status = value; }}
              options={{
                scheduled: app.translator.trans('bigreja-bragalotto.lib.status.scheduled'),
                closed: app.translator.trans('bigreja-bragalotto.lib.status.closed'),
                finished: app.translator.trans('bigreja-bragalotto.lib.status.finished'),
              }}
            />
          </div>

          <div className="Form-group">
            <Button
              className="Button Button--primary"
              type="submit"
              loading={this.loading}
            >
              {app.translator.trans('bigreja-bragalotto.lib.buttons.save')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();

    if (!this.matchDate || this.matchDate.trim() === '') {
      app.alerts.show({ type: 'error' }, app.translator.trans('bigreja-bragalotto.lib.messages.invalid_outcome'));
      return;
    }

    if (!this.cutoffDate || this.cutoffDate.trim() === '') {
      app.alerts.show({ type: 'error' }, app.translator.trans('bigreja-bragalotto.lib.messages.invalid_outcome'));
      return;
    }

    if (this.homeTeamId === '0' || !this.homeTeamId) {
      app.alerts.show({ type: 'error' }, app.translator.trans('bigreja-bragalotto.lib.messages.invalid_outcome'));
      return;
    }

    if (this.awayTeamId === '0' || !this.awayTeamId) {
      app.alerts.show({ type: 'error' }, app.translator.trans('bigreja-bragalotto.lib.messages.invalid_outcome'));
      return;
    }

    if (this.homeTeamId === this.awayTeamId) {
      app.alerts.show({ type: 'error' }, app.translator.trans('bigreja-bragalotto.lib.messages.same_team'));
      return;
    }

    this.loading = true;
    m.redraw();

    const matchDateIso = new Date(this.matchDate).toISOString();
    const cutoffDateIso = new Date(this.cutoffDate).toISOString();

    const data = {
      weekId: this.weekId === '0' ? null : parseInt(this.weekId),
      homeTeamId: parseInt(this.homeTeamId),
      awayTeamId: parseInt(this.awayTeamId),
      matchDate: matchDateIso,
      cutoffDate: cutoffDateIso,
      allowDraw: this.allowDraw,
      status: this.status,
    };

    try {
      const promise = this.event
        ? this.event.save(data)
        : app.store.createRecord('bragalotto-events').save(data);

      await promise;

      this.attrs.onsave();
      this.hide();
    } catch (error: any) {
      this.loading = false;
      this.alertAttrs = error.alert;
      m.redraw();
    }
  }
}