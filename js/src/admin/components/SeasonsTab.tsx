import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import SeasonModal from './modals/SeasonModal';
import Season from '../../common/models/Season';
import extractText from 'flarum/common/utils/extractText';

export default class SeasonsTab extends Component {
  view() {
    const seasons = app.store.all<Season>('bragalotto-seasons');

    return (
      <div className="SeasonsTab">
        <div className="SeasonsTab-header">
          <h3>
            <i className="fas fa-calendar-alt" />
            {app.translator.trans('bigreja-bragalotto.lib.nav.seasons')}
          </h3>
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => app.modal.show(SeasonModal, {
              season: null,
              onsave: () => m.redraw() 
            })}
          >
            {/* GÜNCELLENDİ: Parametre kaldırıldı */}
            {app.translator.trans('bigreja-bragalotto.lib.actions.create')}
          </Button>
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.name')}</div>
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.slug')}</div>
            <div>{app.translator.trans('bigreja-bragalotto.lib.common.date')}</div>
            <div></div>
          </div>

          {seasons.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-calendar-times" />
              <p>{app.translator.trans('bigreja-bragalotto.lib.messages.no_data')}</p>
            </div>
          ) : (
            seasons.map(season => (
              <div key={season.id()} className="CardList-item">
                <div className="CardList-item-cell CardList-item-cell--primary" data-label={app.translator.trans('bigreja-bragalotto.lib.headers.name')}>
                  {season.name()}
                </div>

                <div className="CardList-item-cell CardList-item-cell--muted" data-label={app.translator.trans('bigreja-bragalotto.lib.headers.slug')}>
                  {season.slug()}
                </div>

                <div className="CardList-item-cell" data-label={app.translator.trans('bigreja-bragalotto.lib.common.date')}>
                  {season.startDate() && season.endDate() 
                    ? (
                      <span>
                        {new Date(season.startDate()!).toLocaleDateString()}
                        {' - '}
                        {new Date(season.endDate()!).toLocaleDateString()}
                      </span>
                    )
                    : '-'}
                </div>

                <div className="CardList-item-actions">
                  <Button
                    className="Button Button--primary"
                    icon="fas fa-edit"
                    onclick={() => app.modal.show(SeasonModal, {
                      season: season,
                      onsave: () => m.redraw() 
                    })}
                  >
                    {app.translator.trans('bigreja-bragalotto.lib.buttons.edit')}
                  </Button>
                  <Button
                    className="Button Button--danger"
                    icon="fas fa-trash"
                    onclick={() => this.deleteSeason(season)}
                  >
                    {app.translator.trans('bigreja-bragalotto.lib.buttons.delete')}
                  </Button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    );
  }

  deleteSeason(season: Season) {
    // GÜNCELLENDİ: Parametre kaldırıldı
    const confirmMessage = extractText(app.translator.trans('bigreja-bragalotto.lib.messages.delete_confirm'));

    if (!confirm(confirmMessage)) {
      return;
    }
    season.delete().then(() => {
      m.redraw();
    });
  }
}