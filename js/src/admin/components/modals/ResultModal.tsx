import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import PickemEvent from '../../../common/models/Event';
import Team from '../../../common/models/Team';

interface IResultModalAttrs {
  event: PickemEvent;
  onsave: () => void;
}

export default class ResultModal extends Modal<IResultModalAttrs> {
  private event: PickemEvent;
  private homeScore: string | number = '';
  private awayScore: string | number = '';
  private loading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);

    this.event = this.attrs.event;
    this.homeScore = this.event.homeScore() !== null ? this.event.homeScore() : '';
    this.awayScore = this.event.awayScore() !== null ? this.event.awayScore() : '';
  }

  className(): string {
    return 'ResultModal Modal--small';
  }

  title(): string {
    return app.translator.trans('bigreja-bragalotto.lib.actions.enter_result');
  }

  content() {
    const homeTeam = this.event.homeTeam() as Team | null;
    const awayTeam = this.event.awayTeam() as Team | null;

    let resultText = '';
    const home = Number(this.homeScore);
    const away = Number(this.awayScore);

    if (this.homeScore !== '' && this.awayScore !== '') {
      if (home > away) resultText = homeTeam ? homeTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.home');
      else if (away > home) resultText = awayTeam ? awayTeam.name() : app.translator.trans('bigreja-bragalotto.lib.common.away');
      else resultText = app.translator.trans('bigreja-bragalotto.lib.common.draw');
    }

    return (
      <div className="Modal-body">
        <div className="Form">
          
          <div className="Form-group">
            <div className="TeamCell" style={{marginBottom: '8px'}}>
               {this.renderTeamLogo(homeTeam)}
               <label style={{margin: 0}}>
                 {homeTeam ? homeTeam.name() : app.translator.trans('bigreja-bragalotto.lib.form.home_team')}
               </label>
            </div>
            <input
              className="FormControl"
              type="number"
              min="0"
              value={this.homeScore}
              oninput={(e: InputEvent) => {
                this.homeScore = (e.target as HTMLInputElement).value;
              }}
            />
          </div>

          <div className="Form-group">
            <div className="TeamCell" style={{marginBottom: '8px'}}>
               {this.renderTeamLogo(awayTeam)}
               <label style={{margin: 0}}>
                 {awayTeam ? awayTeam.name() : app.translator.trans('bigreja-bragalotto.lib.form.away_team')}
               </label>
            </div>
            <input
              className="FormControl"
              type="number"
              min="0"
              value={this.awayScore}
              oninput={(e: InputEvent) => {
                this.awayScore = (e.target as HTMLInputElement).value;
              }}
            />
          </div>

          {resultText && (
            <div className="Form-group">
              <p>
                <strong>{app.translator.trans('bigreja-bragalotto.lib.common.result')}: </strong>
                {resultText}
              </p>
            </div>
          )}

          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {app.translator.trans('bigreja-bragalotto.lib.buttons.save')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  renderTeamLogo(team: Team | null) {
    if (!team) {
      return <div className="TeamLogo TeamLogo--letter" style={{ backgroundColor: '#999' }}>?</div>;
    }

    const logoUrl = team.logoUrl();
    const teamName = team.name();
    const firstLetter = teamName ? teamName.charAt(0).toUpperCase() : 'T';
    
    const stringToColor = (str: string) => {
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const hue = hash % 360;
      return `hsl(${hue}, 65%, 50%)`;
    };
    
    if (logoUrl) {
      return (
        <img 
          src={logoUrl} 
          alt={teamName || 'Team'} 
          className="TeamLogo TeamLogo--image" 
        />
      );
    }

    const backgroundColor = stringToColor(teamName || 'Team');
    
    return (
      <div className="TeamLogo TeamLogo--letter" style={{ backgroundColor }}>
        {firstLetter}
      </div>
    );
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();
    this.loading = true;
    m.redraw();

    try {
      const response = await app.request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/bragalotto-events/${this.event.id()}/result`,
        body: {
          data: {
            type: 'bragalotto-events',
            attributes: {
              homeScore: parseInt(this.homeScore as string) || 0,
              awayScore: parseInt(this.awayScore as string) || 0,
            },
          },
        },
      });

      app.store.pushPayload(response);

      app.alerts.show({ type: 'success' }, app.translator.trans('bigreja-bragalotto.lib.messages.result_saved'));
      this.attrs.onsave();
      this.hide();

    } catch (error: any) {
      this.loading = false;
      this.alertAttrs = error.alert;
      m.redraw();
    }
  }
}