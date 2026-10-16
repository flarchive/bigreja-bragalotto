import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import TeamModal from './modals/TeamModal';
import Team from '../../common/models/Team';
import extractText from 'flarum/common/utils/extractText';

export default class TeamsTab extends Component {
  view() {
    const teams = app.store.all<Team>('bragalotto-teams');

    return (
      <div className="TeamsTab">
        <div className="TeamsTab-header">
          <h3>
            <i className="fas fa-users" />
            {app.translator.trans('bigreja-bragalotto.lib.nav.teams')}
          </h3>
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => app.modal.show(TeamModal, {
              team: null,
              onsave: () => m.redraw() 
            })}
          >
            {/* GÜNCELLENDİ: Parametre kaldırıldı */}
            {app.translator.trans('bigreja-bragalotto.lib.actions.create')}
          </Button>
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.logo')}</div>
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.name')}</div>
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.slug')}</div>
            <div></div>
          </div>

          {teams.map(team => (
            <div key={team.id()} className="CardList-item">
              <div className="CardList-item-cell" data-label={app.translator.trans('bigreja-bragalotto.lib.headers.logo')}>
                {this.renderTeamLogo(team)}
              </div>

              <div className="CardList-item-cell CardList-item-cell--primary" data-label={app.translator.trans('bigreja-bragalotto.lib.headers.name')}>
                {team.name()}
              </div>

              <div className="CardList-item-cell CardList-item-cell--muted" data-label={app.translator.trans('bigreja-bragalotto.lib.headers.slug')}>
                {team.slug()}
              </div>

              <div className="CardList-item-actions">
                <Button
                  className="Button Button--primary"
                  icon="fas fa-edit"
                  onclick={() => app.modal.show(TeamModal, {
                    team: team,
                    onsave: () => m.redraw()
                  })}
                >
                  {app.translator.trans('bigreja-bragalotto.lib.buttons.edit')}
                </Button>
                <Button
                  className="Button Button--danger"
                  icon="fas fa-trash"
                  onclick={() => this.deleteTeam(team)}
                >
                  {app.translator.trans('bigreja-bragalotto.lib.buttons.delete')}
                </Button>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  renderTeamLogo(team: Team) {
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
    
    const backgroundColor = stringToColor(teamName || 'Team');

    if (logoUrl) {
      return <img src={logoUrl} alt={teamName || 'Team'} className="TeamLogo TeamLogo--image" />;
    }

    return (
      <div className="TeamLogo TeamLogo--letter" style={{ backgroundColor }}>
        {firstLetter}
      </div>
    );
  }

  deleteTeam(team: Team) {
    // GÜNCELLENDİ: Parametre kaldırıldı
    const confirmMessage = extractText(app.translator.trans('bigreja-bragalotto.lib.messages.delete_confirm'));
    
    if (!confirm(confirmMessage)) {
      return;
    }
    team.delete().then(() => {
      m.redraw();
    });
  }
}