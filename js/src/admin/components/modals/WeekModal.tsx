import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Week from '../../../common/models/Week';
import Competition from '../../../common/models/Competition';

interface IWeekModalAttrs {
  week?: Week | null;
  onsave: () => void;
}

export default class WeekModal extends Modal<IWeekModalAttrs> {
  private week: Week | null | undefined;
  private name: string = '';
  private weekNumber: string | number = '';
  private competitionId: string = '0';
  private loading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.week = this.attrs.week;
    if (this.week) {
      this.name = this.week.name() || '';
      this.weekNumber = this.week.weekNumber() || '';
      this.competitionId = String(this.week.competitionId() || '0');
    }
  }

  className(): string {
    return 'WeekModal Modal--small';
  }

  title(): string {
    // GÜNCELLENDİ: resource değişkeni ve parametreler kaldırıldı
    return this.week
      ? app.translator.trans('bigreja-bragalotto.lib.actions.edit')
      : app.translator.trans('bigreja-bragalotto.lib.actions.create');
  }

  content() {
    const competitions = app.store.all<Competition>('bragalotto-competitions');
    const competitionOptions: Record<string, string> = competitions.reduce((acc: Record<string, string>, c) => {
      acc[c.id()!] = c.name()!;
      return acc;
    }, {});

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.name')}</label>
            <input
              className="FormControl"
              type="text"
              value={this.name}
              oninput={(e: InputEvent) => { this.name = (e.target as HTMLInputElement).value; }}
            />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.nav.competitions')}</label>
            <Select
              className="FormControl"
              value={this.competitionId}
              onchange={(value: string) => { this.competitionId = value; }}
              options={competitionOptions}
              default="0"
            >
              <option value="0">{app.translator.trans('bigreja-bragalotto.lib.form.select_season')}</option>
            </Select>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.week_number')}</label>
            <input
              className="FormControl"
              type="number"
              value={this.weekNumber}
              oninput={(e: InputEvent) => { this.weekNumber = (e.target as HTMLInputElement).value; }}
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
    this.loading = true;
    m.redraw();
    const data = {
      name: this.name,
      weekNumber: this.weekNumber || null,
      competitionId: this.competitionId === '0' ? null : this.competitionId,
    };
    try {
      const promise = this.week
        ? this.week.save(data)
        : app.store.createRecord('bragalotto-weeks').save(data);

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