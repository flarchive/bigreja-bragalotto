import Model from 'flarum/common/Model';

export default class Team extends Model {
  name = Model.attribute<string>('name');
  slug = Model.attribute<string>('slug');
  logoPath = Model.attribute<string>('logoPath');
  logoUrl = Model.attribute<string | null>('logoUrl');
}