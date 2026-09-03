import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import { slug } from 'flarum/common/utils/string';
import Competition from '../../common/models/Competition';
import Season from '../../common/models/Season';

export default class CompetitionsTab extends Component {
  view() {
    const competitions = app.store.all<Competition>('bragalotto-competitions');

    return (
      <div className="CompetitionsTab">
        <div className="CompetitionsTab-header">
          <h3>
            <i className="fas fa-trophy" />
            {app.translator.trans('bigreja-bragalotto.lib.nav.competitions')}
          </h3>
          <Button
            className="Button Button--primary"
            icon="fas fa-plus"
            onclick={() => this.showModal(null)}
          >
            {app.translator.trans('bigreja-bragalotto.lib.actions.create')}
          </Button>
        </div>

        <div className="CardList">
          <div className="CardList-header">
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.name')}</div>
            <div>{app.translator.trans('bigreja-bragalotto.lib.common.season')}</div>
            <div>{app.translator.trans('bigreja-bragalotto.lib.headers.slug')}</div>
            <div></div>
          </div>

          {competitions.length === 0 ? (
            <div className="EmptyState">
              <i className="fas fa-trophy" />
              <p>{app.translator.trans('bigreja-bragalotto.lib.messages.no_data')}</p>
            </div>
          ) : (
            competitions.map((competition) => {
              const season = app.store.all<Season>('bragalotto-seasons').find(
                (s) => String(s.id()) === String(competition.seasonId())
              );
              return (
                <div key={competition.id()} className="CardList-item">
                  <div className="CardList-item-cell CardList-item-cell--primary">
                    {competition.name()}
                  </div>
                  <div className="CardList-item-cell">
                    {season ? season.name() : '-'}
                  </div>
                  <div className="CardList-item-cell CardList-item-cell--muted">
                    {competition.slug()}
                  </div>
                  <div className="CardList-item-actions">
                    <Button
                      className="Button Button--primary"
                      icon="fas fa-edit"
                      onclick={() => this.showModal(competition)}
                    >
                      {app.translator.trans('bigreja-bragalotto.lib.buttons.edit')}
                    </Button>
                    <Button
                      className="Button Button--danger"
                      icon="fas fa-trash"
                      onclick={() => this.deleteCompetition(competition)}
                    >
                      {app.translator.trans('bigreja-bragalotto.lib.buttons.delete')}
                    </Button>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </div>
    );
  }

  showModal(competition: Competition | null) {
    const seasons = app.store.all<Season>('bragalotto-seasons');

    app.modal.show(() => {
      let name = competition ? competition.name() : '';
      let slugVal = competition ? competition.slug() : '';
      let seasonId = competition ? String(competition.seasonId()) : (seasons[0] ? String(seasons[0].id()) : '');
      let loading = false;

      const save = async (e: SubmitEvent) => {
        e.preventDefault();
        loading = true;
        m.redraw();
        const data = { name, slug: slugVal, seasonId };
        try {
          if (competition) {
            await competition.save(data);
          } else {
            await app.store.createRecord<Competition>('bragalotto-competitions').save(data);
          }
          app.modal.close();
          m.redraw();
        } catch (err: any) {
          loading = false;
          m.redraw();
        }
      };

      return {
        className: () => 'CompetitionModal Modal--small',
        title: () => competition
          ? app.translator.trans('bigreja-bragalotto.lib.actions.edit')
          : app.translator.trans('bigreja-bragalotto.lib.actions.create'),
        content: () => (
          <div className="Modal-body">
            <div className="Form">
              <div className="Form-group">
                <label>{app.translator.trans('bigreja-bragalotto.lib.form.name')}</label>
                <input className="FormControl" type="text" value={name}
                  oninput={(e: InputEvent) => {
                    name = (e.target as HTMLInputElement).value;
                    if (!competition) slugVal = slug(name);
                  }} />
              </div>
              <div className="Form-group">
                <label>{app.translator.trans('bigreja-bragalotto.lib.form.slug')}</label>
                <input className="FormControl" type="text" value={slugVal}
                  oninput={(e: InputEvent) => { slugVal = (e.target as HTMLInputElement).value; }} />
              </div>
              <div className="Form-group">
                <label>{app.translator.trans('bigreja-bragalotto.lib.common.season')}</label>
                <select className="FormControl" value={seasonId}
                  onchange={(e: Event) => { seasonId = (e.target as HTMLSelectElement).value; }}>
                  {seasons.map((s) => (
                    <option value={String(s.id())}>{s.name()}</option>
                  ))}
                </select>
              </div>
              <div className="Form-group">
                <Button className="Button Button--primary" type="submit" loading={loading}
                  onclick={save}>
                  {app.translator.trans('bigreja-bragalotto.lib.buttons.save')}
                </Button>
              </div>
            </div>
          </div>
        ),
      };
    });
  }

  deleteCompetition(competition: Competition) {
    const msg = extractText(app.translator.trans('bigreja-bragalotto.lib.messages.delete_confirm'));
    if (!confirm(msg)) return;
    competition.delete().then(() => m.redraw());
  }
}
