import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';

export default class SettingsTab extends Component {
  loading: boolean = false;
  reverseDisplay: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.reverseDisplay = app.data.settings['bigreja-bragalotto.reverse_display'] === '1';
  }

  view() {
    return (
      <div className="SettingsTab">
        <div className="Form-group">
          <h3>
            <i className="fas fa-cogs" />
            {app.translator.trans('bigreja-bragalotto.lib.nav.settings')}
          </h3>
          
          <div className="Form-group">
            <Switch
              state={this.reverseDisplay}
              onchange={this.toggleReverseDisplay.bind(this)}
            >
              {app.translator.trans('bigreja-bragalotto.admin.settings.reverse_display_label')}
            </Switch>
            <div className="helpText">
              {app.translator.trans('bigreja-bragalotto.admin.settings.reverse_display_help')}
            </div>
          </div>

          <hr />

          <p>
            {app.translator.trans('bigreja-bragalotto.admin.settings.recalc_help')}
          </p>
          <Button
            className="Button Button--primary"
            icon="fas fa-sync"
            loading={this.loading}
            onclick={this.recalculateScores.bind(this)}
          >
            {app.translator.trans('bigreja-bragalotto.admin.settings.recalc_btn')}
          </Button>
        </div>
      </div>
    );
  }

  toggleReverseDisplay(value: boolean) {
    this.reverseDisplay = value;
    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/settings',
      body: {
        'bigreja-bragalotto.reverse_display': value ? '1' : '0'
      }
    });
  }

  recalculateScores() {
    if (this.loading) return;

    if (!confirm(app.translator.trans('bigreja-bragalotto.admin.settings.recalc_confirm'))) {
      return;
    }

    this.loading = true;
    m.redraw();
    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/bragalotto/recalculate-all-scores',
    }).then(response => {
      this.loading = false;
      m.redraw();
      app.alerts.show({ type: 'success' }, app.translator.trans('bigreja-bragalotto.admin.settings.recalc_queued'));
    }).catch(error => {
      this.loading = false;
      m.redraw();
      console.error(error);
    });
  }
}