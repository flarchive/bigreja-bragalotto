import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import { slug } from 'flarum/common/utils/string';
import Team from '../../../common/models/Team'; 

interface ITeamModalAttrs {
  team?: Team | null;
  onsave: () => void;
}

export default class TeamModal extends Modal<ITeamModalAttrs> {
  private team: Team | null | undefined;
  private name: string = '';
  private slug: string = '';
  private logoPath: string = '';
  private loading: boolean = false;
  // YENİ: Yükleme durumu için state
  private uploadLoading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);
    this.team = this.attrs.team;
    if (this.team) {
      this.name = this.team.name() || '';
      this.slug = this.team.slug() || '';
      this.logoPath = this.team.logoPath() || '';
    }
  }

  className(): string {
    return 'TeamModal Modal--small';
  }

  title(): string {
    return this.team
      ? app.translator.trans('bigreja-bragalotto.lib.actions.edit')
      : app.translator.trans('bigreja-bragalotto.lib.actions.create');
  }

  content() {
    // FoF Upload uzantısının aktif olup olmadığını kontrol et
    const hasFofUpload = 'fof-upload' in app.data.extensions;

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.name')}</label>
            <input
              className="FormControl"
              type="text"
              value={this.name}
              oninput={(e: InputEvent) => {
                this.name = (e.target as HTMLInputElement).value;
                if (!this.team) {
                  this.slug = slug(this.name);
                }
              }}
            />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.slug')}</label>
            <input
              className="FormControl"
              type="text"
              value={this.slug}
              oninput={(e: InputEvent) => { this.slug = (e.target as HTMLInputElement).value; }}
            />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('bigreja-bragalotto.lib.form.logo_url')}</label>
            
            {/* YENİ: Input ve Upload butonu yan yana */}
            <div style={{ display: 'flex', gap: '8px' }}>
              <input
                className="FormControl"
                type="text"
                value={this.logoPath}
                oninput={(e: InputEvent) => { this.logoPath = (e.target as HTMLInputElement).value; }}
                placeholder="https://example.com/logo.png"
                style={{ flex: 1 }}
              />
              
              {hasFofUpload && (
                <>
                  <input
                    id="pickem-logo-upload"
                    type="file"
                    accept="image/*"
                    style={{ display: 'none' }}
                    onchange={this.handleUpload.bind(this)}
                  />
                  <Button
                    className="Button Button--icon"
                    icon="fas fa-cloud-upload-alt"
                    loading={this.uploadLoading}
                    onclick={() => {
                        const fileInput = document.getElementById('pickem-logo-upload');
                        if (fileInput) fileInput.click();
                    }}
                    title="Upload with FoF Upload"
                    type="button"
                  />
                </>
              )}
            </div>

            {this.logoPath && (
              <div style={{ marginTop: '10px' }}>
                <img
                  src={this.logoPath}
                  alt="Logo preview"
                  style={{ maxWidth: '100px', maxHeight: '100px', border: '1px solid #ddd', padding: '5px', borderRadius: '4px' }}
                  onerror={(e: any) => { e.target.style.display = 'none'; }}
                />
              </div>
            )}
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

  // YENİ: Dosya yükleme işleyicisi
  async handleUpload(e: Event) {
    const target = e.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('files[]', file);

    this.uploadLoading = true;
    m.redraw();

    try {
      // FoF Upload API'sine istek at
      const response: any = await app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/fof/upload',
        body: formData,
        serialize: (raw: any) => raw // FormData'yı JSON'a çevirme, olduğu gibi gönder
      });

      // Gelen yanıttan URL'i al (FoF Upload JSON:API standardı kullanır)
      if (response && response.data && response.data[0] && response.data[0].attributes) {
        this.logoPath = response.data[0].attributes.url;
        app.alerts.show({ type: 'success' }, app.translator.trans('bigreja-bragalotto.lib.messages.saved')); // Opsiyonel başarı mesajı
      }
    } catch (error: any) {
      console.error('Logo upload failed:', error);
      app.alerts.show({ type: 'error' }, 'Upload failed. Check console for details.');
    } finally {
      this.uploadLoading = false;
      target.value = ''; // Inputu temizle ki aynı dosya tekrar seçilebilsin
      m.redraw();
    }
  }

  async onsubmit(e: SubmitEvent) {
    e.preventDefault();
    this.loading = true;
    m.redraw();
    const data = {
      name: this.name,
      slug: this.slug,
      logoPath: this.logoPath,
    };
    try {
      const promise = this.team
        ? this.team.save(data)
        : app.store.createRecord('bragalotto-teams').save(data);

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