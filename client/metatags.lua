-- Set the response content type back to HTML
ngx.header.content_type = 'text/html';

ngx.req.read_body()

local page_html = ngx.location.capture("/index.htm").body

local before_content, placeholder, after_content
before_content, placeholder, after_content = page_html:match(
  string.format("^(.*)(%s)(.*)$", html_head_tag_replacement_str)
)

-- check for placeholder to replace
if not placeholder then
  ngx.log(ngx.STDERR, "WARNING: Meta tag placeholder was not found in page html.")
  ngx.say(page_html)
  return
else
  -- start the response
  ngx.print(before_content)
  -- send partial content to the client to allow preloading the app
  ngx.flush()
end

ngx.req.set_header("Accept", "application/json")
local server_info_resp = ngx.location.capture("/_internal_api/info")
if server_info_resp.status ~= 200 then
  ngx.log(ngx.STDERR, "Failed to acquire server info from szurubooru API, unable to generate meta tags: HTTP status "..server_info_resp.status)
  ngx.print(after_content)
  return
end
local server_info = cjson.decode(server_info_resp.body)

local additional_tags = ""
local function add_meta_tag (property, content)
  -- NOTE do not allow user-provided data in the property name, only the content has quotes escaped
  additional_tags = additional_tags .. "<meta property=\"" .. property .. "\" content=\"" .. tostring(content):gsub('"', '\\"') .. "\"/>"
end

-- Add the site name tag
add_meta_tag("og:site_name", server_info.config.name)
add_meta_tag("og:url", ngx.var.external_host_url .. ngx.var.request_uri_path)

if ngx.var.request_uri_path:match('^/$') then -- Site root
  add_meta_tag("og:type", "website")
  add_meta_tag("og:title", server_info.config.name)
  add_meta_tag("twitter:title", server_info.config.name)
  -- if there's a featured post, let's use that as the image
  if server_info.featuredPost then
    local post_info = server_info.featuredPost
    -- NOTE this is different from the normal Post tags,
    -- notably we avoid setting the article type, author, time, etc
    local og_media_prefix
    if post_info.type == "image" then
      og_media_prefix = "og:image"
      add_meta_tag("twitter:card", "summary_large_image")
      add_meta_tag("twitter:image", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
    elseif post_info.type == "video" then
      og_media_prefix = "og:video"
      -- some sites don't preview video, so at least provide a thumbnail
      add_meta_tag("og:image", ngx.var.external_host_url .. '/' .. post_info.thumbnailUrl)
    end
    add_meta_tag(og_media_prefix..":url", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
    add_meta_tag(og_media_prefix..":width", post_info.canvasWidth)
    add_meta_tag(og_media_prefix..":height", post_info.canvasHeight)
  end
elseif ngx.var.request_uri_path:match('^/post/([0-9]+)/?$') then -- Post metadata
  -- check if posts are accessible to anonymous users:
  if server_info.config.privileges["posts:view"] == "anonymous" then
    add_meta_tag("og:type", "article")
    local post_info = cjson.decode((ngx.location.capture("/_internal_api"..ngx.var.request_uri_path)).body)
    add_meta_tag("og:title", server_info.config.name .. " - Post " .. post_info.id)
    add_meta_tag("twitter:title", server_info.config.name .. " - Post " .. post_info.id)
    add_meta_tag("article:published_time", post_info.creationTime)
    local og_media_prefix
    if post_info.type == "image" then
      og_media_prefix = "og:image"
      add_meta_tag("twitter:card", "summary_large_image")
      add_meta_tag("twitter:image", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
    elseif post_info.type == "video" then
      og_media_prefix = "og:video"
      -- some sites don't preview video, so at least provide a thumbnail
      add_meta_tag("og:image", ngx.var.external_host_url .. '/' .. post_info.thumbnailUrl)
    end
    add_meta_tag(og_media_prefix..":url", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
    add_meta_tag(og_media_prefix..":width", post_info.canvasWidth)
    add_meta_tag(og_media_prefix..":height", post_info.canvasHeight)
    -- user is not present for anonymous uploads:
    if post_info.user then
      add_meta_tag("article:author", post_info.user.name)
    end
  else
    -- no permission to retrieve post data
    add_meta_tag("og:title", server_info.config.name .. " - Login required")
  end
elseif ngx.var.request_uri_path:match('^/user/([^/]+)/?$') then -- User metadata
  local username = ngx.var.request_uri_path:match('^/user/([^/]+)/?$')
  add_meta_tag("og:title", server_info.config.name .. " - " .. username)
  add_meta_tag("og:type", "profile")
  -- check for permission to access user profiles
  if server_info.config.privileges["users:view"] == "anonymous" then
    local user_info = cjson.decode((ngx.location.capture("/_internal_api/user/"..username)).body)
    add_meta_tag("profile:username", user_info.name)
    local avatar_url
    avatar_url = user_info.avatarUrl
    if avatar_url:match("^https?://") then
      add_meta_tag("og:image", avatar_url)
    else
      add_meta_tag("og:image", ngx.var.external_host_url .. '/' .. avatar_url)
    end
  else
    -- no permission to view user data
  end
end

-- Once tags have been generated, write them and then finish the response
ngx.print(additional_tags)
ngx.print(after_content)
